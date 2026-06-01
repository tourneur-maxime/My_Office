<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Jobs\GenerateQuotePdfJob;
use App\Models\Prospect;
use App\Models\Quote;
use App\Services\QuotePdfService;
use App\Services\QuoteService;
use App\Services\InvoiceService; // Add this line
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class QuoteController extends Controller
{
    protected QuoteService $quoteService;

    protected QuotePdfService $quotePdfService;

    protected InvoiceService $invoiceService; // Declare the new service

    public function __construct(QuoteService $quoteService, QuotePdfService $quotePdfService, InvoiceService $invoiceService) // Inject the new service
    {
        $this->quoteService = $quoteService;
        $this->quotePdfService = $quotePdfService;
        $this->invoiceService = $invoiceService; // Assign the new service
    }

    public function create(Request $request): InertiaResponse
    {
        return Inertia::render('DocumentBuilder', [
            'type' => 'quote',
            'clients' => $request->user()->prospects()
                ->select('id', 'name', 'company', 'status')
                ->get(),
            'templates' => $request->user()->templates()->get(),
            'companyProfile' => $request->user()->companyProfile,
        ]);
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'exists:prospects,id'],
            'template_id' => ['nullable', 'exists:templates,id'],
            'expires_at' => ['nullable', 'date'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string'],
            'line_items.*.quantity' => ['required', 'numeric'],
            'line_items.*.unit_price' => ['required', 'numeric'],
            'line_items.*.vat_rate' => ['required', 'numeric'],
        ]);

        $prospect = Prospect::findOrFail($validated['client_id']);
        $template = isset($validated['template_id']) ? \App\Models\Template::find($validated['template_id']) : null;

        // Create a temporary Quote object in memory
        $quote = new Quote([
            'user_id' => $request->user()->id,
            'client_id' => $prospect->id,
            'expires_at' => $validated['expires_at'] ? new \DateTimeImmutable($validated['expires_at']) : null,
            'created_at' => now(),
        ]);
        $quote->setRelation('user', $request->user());
        $quote->setRelation('client', $prospect);

        // Calculate totals manually for the temporary quote
        $subtotal = 0;
        $vatAmount = 0;
        $lineItems = collect();

        foreach ($validated['line_items'] as $itemData) {
            $lineItem = new \App\Models\QuoteLineItem($itemData);
            $itemTotal = round($lineItem->quantity * $lineItem->unit_price, 2);
            $itemVat = round($itemTotal * ($lineItem->vat_rate / 100), 2);
            $subtotal += $itemTotal;
            $vatAmount += $itemVat;
            $lineItems->push($lineItem);
        }

        $quote->subtotal = $subtotal;
        $quote->vat_amount = $vatAmount;
        $quote->total = $subtotal + $vatAmount;
        $quote->setRelation('lineItems', $lineItems);

        $pdfContent = $this->quotePdfService->generate($quote, $template);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="preview.pdf"');
    }

    public function store(StoreQuoteRequest $request, Prospect $prospect): RedirectResponse
    {
        Gate::authorize('update', $prospect);

        $validated = $request->validated();

        $quote = $this->quoteService->createQuote(
            $prospect,
            $validated['line_items'],
            isset($validated['expires_at']) ? new \DateTimeImmutable($validated['expires_at']) : null,
            $validated['template_id'] ?? null,
            $validated['layout_configuration'] ?? null
        );

        return redirect()->route('quotes.index')->with('success', 'Devis créé avec succès.');
    }

    public function index(Request $request): InertiaResponse
    {
        $filters = $request->only('client_id', 'status');

        $quotes = Quote::query()
            ->where('user_id', $request->user()->id)
            ->when($request->has('client_id'), function ($query) use ($request) {
                $query->where('client_id', $request->input('client_id'));
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->with(['client', 'invoices']) // Eager load relationships
            ->latest()
            ->paginate(10);

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => $filters,
            'quoteStatuses' => \App\Enums\QuoteStatus::values(), // Pass statuses for filtering dropdown
            'prospects' => $request->user()->prospects()->select('id', 'name', 'company')->get(), // Pass prospects for client filtering
        ]);
    }

    public function updateStatus(Request $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);

        // Prevent status update if the quote has already been converted to an invoice
        if ($quote->invoices()->exists()) {
            return redirect()->back()->with('error', 'Le statut d\'un devis converti en facture ne peut pas être modifié.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', \Illuminate\Validation\Rule::in(\App\Enums\QuoteStatus::values())],
        ]);

        $oldStatus = $quote->status;
        $newStatus = \App\Enums\QuoteStatus::from($validated['status']);

        $quote->update(['status' => $validated['status']]);

        // Send notification if status changed
        if ($oldStatus !== $newStatus) {
            $quote->user->notify(new \App\Notifications\QuoteStatusChangedNotification(
                quoteNumber: $quote->quote_number,
                quoteId: $quote->id,
                oldStatus: $oldStatus,
                newStatus: $newStatus
            ));
        }

        return redirect()->back()->with('success', 'Statut du devis mis à jour avec succès.');
    }

    public function edit(Request $request, Quote $quote): InertiaResponse
    {
        Gate::authorize('update', $quote);

        // Eager load relationships
        $quote->load(['client', 'lineItems', 'invoices']);

        return Inertia::render('DocumentBuilder', [
            'type' => 'quote',
            'quote' => $quote,
            'clients' => $request->user()->prospects()
                ->select('id', 'name', 'company', 'status')
                ->get(),
            'templates' => $request->user()->templates()->get(),
            'companyProfile' => $request->user()->companyProfile,
        ]);
    }

    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);

        // Only allow updates if the quote is in 'Brouillon' status and has not been converted to an invoice
        if ($quote->status !== QuoteStatus::Brouillon) {
            return redirect()->back()->with('error', 'Seuls les devis en statut Brouillon peuvent être modifiés.');
        }

        if ($quote->invoices()->exists()) {
            return redirect()->back()->with('error', 'Ce devis ne peut pas être modifié car il a déjà été converti en facture.');
        }

        $validated = $request->validated();

        $this->quoteService->updateQuote($quote, $validated);

        return redirect()->back()->with('success', 'Devis mis à jour avec succès.');
    }

    public function duplicate(Quote $quote): RedirectResponse
    {
        Gate::authorize('view', $quote);

        $newQuote = $this->quoteService->duplicateQuote($quote);

        return redirect()->route('quotes.edit', $newQuote->id)->with('success', 'Devis dupliqué avec succès.');
    }

    public function convertToInvoice(Quote $quote): RedirectResponse
    {
        Gate::authorize('update', $quote);

        // Only allow conversion if the quote is in 'Approuvé' status
        if ($quote->status !== QuoteStatus::Approuvé) {
            return redirect()->back()->with('error', 'Seuls les devis approuvés peuvent être convertis en facture.');
        }

        $invoice = $this->invoiceService->createFromQuote($quote);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Devis converti en facture avec succès.');
    }

    public function show(Quote $quote): InertiaResponse
    {
        Gate::authorize('view', $quote);

        $quote->load(['lineItems', 'client', 'invoices']);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
            'quoteStatuses' => QuoteStatus::values(),
        ]);
    }

    public function generatePdf(Quote $quote): RedirectResponse
    {
        Gate::authorize('view', $quote);

        GenerateQuotePdfJob::dispatch($quote);

        return redirect()->back()->with('success', 'La génération du PDF a été lancée. Le fichier sera disponible sous peu.');
    }

    public function download(Quote $quote)
    {
        Gate::authorize('view', $quote);

        $year = $quote->created_at->format('Y');
        $month = $quote->created_at->format('m');
        $filename = 'devis-'.$quote->quote_number.'.pdf';
        $file_path = "quotes/{$year}/{$month}/{$filename}";

        if (! Storage::exists($file_path)) {
            return redirect()->back()->with('error', 'Le PDF n\'existe pas encore. Veuillez d\'abord générer le PDF.');
        }

        return Storage::download($file_path, $filename);
    }

    /**
     * Delete a quote.
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        Gate::authorize('delete', $quote);

        try {
            // Delete PDF file if exists
            $year = $quote->created_at->format('Y');
            $month = $quote->created_at->format('m');
            $filename = 'devis-'.$quote->quote_number.'.pdf';
            $file_path = "quotes/{$year}/{$month}/{$filename}";

            if (Storage::exists($file_path)) {
                Storage::delete($file_path);
            }

            // Delete the quote (line items will be deleted via cascade)
            $quote->delete();

            return redirect()->route('quotes.index')->with('success', 'Devis supprimé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la suppression du devis.');
        }
    }
}
