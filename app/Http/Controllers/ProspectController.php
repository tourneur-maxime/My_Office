<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProspectRequest;
use App\Models\Prospect;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProspectController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Prospect::class);

        $query = Prospect::where('user_id', $request->user()->id);

        if ($request->has('status') && in_array($request->input('status'), ['prospect', 'client'])) {
            $query->where('status', $request->input('status'));
        }

        $prospects = $query->latest()->paginate(10);

        return Inertia::render('Clients/Index', [
            'prospects' => $prospects,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Prospect::class);

        return Inertia::render('Clients/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProspectRequest $request)
    {
        $this->authorize('create', Prospect::class);

        $validated = $request->validated();
        $notes = $validated['notes'] ?? null;
        unset($validated['notes']); // Remove notes from prospect data

        $prospect = $request->user()->prospects()->create($validated);

        // Create initial note if provided
        if ($notes) {
            $prospect->notes()->create([
                'content' => $notes,
                'user_id' => $request->user()->id,
            ]);
        }

        return redirect()->route('clients.index')->with('success', 'Prospect créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Prospect $prospect)
    {
        // Policy check for authorization
        $this->authorize('view', $prospect);

        return Inertia::render('Clients/Show', [
            'prospect' => $prospect, // No eager loading here, history API will fetch it
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        return Inertia::render('Clients/Edit', [
            'prospect' => $prospect->load('notes'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\UpdateProspectRequest $request, Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $prospect->update($request->validated());

        return redirect()->route('clients.show', $prospect)->with('success', 'Client mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Prospect $prospect)
    {
        $this->authorize('delete', $prospect);

        // Check for associated quotes or invoices
        if ($prospect->quotes()->exists() || $prospect->invoices()->exists()) {
            return redirect()->back()->with('error', 'Impossible de supprimer un client avec des devis ou factures associés.');
        }

        $prospect->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprimé avec succès.');
    }

    /**
     * Convert a prospect to a client.
     */
    public function convertToClient(Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        if ($prospect->status === 'client') {
            return redirect()->back()->with('error', 'Ce contact est déjà un client.');
        }

        $prospect->update(['status' => 'client']);

        return redirect()->route('clients.show', $prospect)->with('success', 'Prospect converti en client avec succès.');
    }

    /**
     * Search for prospects or clients based on a query.
     */
    public function search(Request $request)
    {
        $this->authorize('viewAny', Prospect::class); // Assuming a policy for searching

        $query = $request->input('q');
        $type = $request->input('type'); // 'prospect', 'client', or null for all
        $limit = $request->input('limit', 10);

        $prospects = $request->user()->prospects()
            ->when($type, function ($q) use ($type) {
                $q->where('status', $type);
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%")
                    ->orWhere('siret', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $prospects,
                'total' => $prospects->count(),
            ],
            'message' => 'Résultats de recherche récupérés avec succès.',
        ]);
    }

    /**
     * Get suggestions for client autocomplete.
     */
    public function suggestions(Request $request)
    {
        $this->authorize('viewAny', Prospect::class);

        $query = $request->input('search');
        $limit = $request->input('limit', 5);

        $suggestions = $request->user()->prospects()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('company', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'name', 'company']); // Select only necessary fields

        return response()->json($suggestions); // Tests expect direct array, not wrapped
    }

    /**
     * Fetch the history (quotes and invoices) for a specific prospect.
     */
    public function history(Request $request, Prospect $prospect)
    {
        $this->authorize('view', $prospect); // Authorization check

        $quotesQuery = $prospect->quotes();
        $invoicesQuery = $prospect->invoices();

        // Apply filters for quotes
        if ($request->has('quote_status')) {
            $quotesQuery->where('status', $request->input('quote_status'));
        }
        // Apply sorting
        $this->applySafeSort($quotesQuery, $request, 'quote');

        // Apply filters for invoices
        if ($request->has('invoice_status')) {
            $invoicesQuery->where('status', $request->input('invoice_status'));
        }
        $this->applySafeSort($invoicesQuery, $request, 'invoice');

        return response()->json([
            'success' => true,
            'data' => [
                'quotes' => $quotesQuery->get(),
                'invoices' => $invoicesQuery->get(),
            ],
            'message' => 'Historique du prospect récupéré avec succès.',
        ]);
    }

    /**
     * Toggle favorite status for a prospect/client.
     */
    public function toggleFavorite(Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $prospect->update([
            'is_favorite' => !$prospect->is_favorite
        ]);

        return back()->with('success',
            $prospect->is_favorite ? 'Ajouté aux favoris' : 'Retiré des favoris'
        );
    }

    /**
     * Get favorite prospects/clients.
     */
    public function favorites(Request $request)
    {
        $this->authorize('viewAny', Prospect::class);

        $favorites = $request->user()->prospects()
            ->where('is_favorite', true)
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    private function applySafeSort($query, Request $request, string $prefix): void
    {
        $allowedColumns = ['created_at', 'updated_at', 'status', 'total', 'quote_number', 'invoice_number'];
        $allowedDirections = ['asc', 'desc'];

        if ($request->has("{$prefix}_sort_by")) {
            $sortBy = in_array($request->input("{$prefix}_sort_by"), $allowedColumns)
                ? $request->input("{$prefix}_sort_by") : 'created_at';
            $sortDirection = in_array($request->input("{$prefix}_sort_direction"), $allowedDirections)
                ? $request->input("{$prefix}_sort_direction") : 'asc';
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest();
        }
    }
}
