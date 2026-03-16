<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentTemplateController extends Controller
{
    /**
     * Get all templates for the authenticated user
     */
    public function index(Request $request)
    {
        $type = $request->query('type'); // quote or invoice

        $query = Auth::user()->documentTemplates()->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        return response()->json($query->get());
    }

    /**
     * Store a new template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:quote,invoice',
            'blocks' => 'required|array',
        ]);

        $template = Auth::user()->documentTemplates()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template sauvegardé avec succès',
            'template' => $template,
        ], 201);
    }

    /**
     * Get a specific template
     */
    public function show(DocumentTemplate $template)
    {
        $this->authorize('view', $template);

        return response()->json($template);
    }

    /**
     * Update a template
     */
    public function update(Request $request, DocumentTemplate $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'blocks' => 'sometimes|array',
        ]);

        $template->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Template mis à jour avec succès',
            'template' => $template,
        ]);
    }

    /**
     * Delete a template
     */
    public function destroy(DocumentTemplate $template)
    {
        $this->authorize('delete', $template);

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template supprimé avec succès',
        ]);
    }
}
