<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Note;
use App\Models\Prospect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Import NoteRequest

class NoteController extends Controller
{
    public function store(NoteRequest $request, Prospect $prospect): RedirectResponse // Use NoteRequest
    {
        // Authorization: Ensure only the owner of the prospect can add notes
        // This can be moved to a NotePolicy if needed, but for now, keep it here.
        if ($request->user()->id !== $prospect->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $prospect->notes()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'), // Use validated data from NoteRequest
        ]);

        return redirect()->back()->with('success', 'Note ajoutée avec succès');
    }

    public function update(NoteRequest $request, Prospect $prospect, Note $note): RedirectResponse // Use NoteRequest
    {
        // Authorization: Ensure only the owner of the prospect can update notes
        if ($request->user()->id !== $prospect->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the note belongs to this prospect
        if ($note->prospect_id !== $prospect->id) {
            abort(404, 'Note not found for this prospect.');
        }

        $note->update($request->validated()); // Use validated data from NoteRequest

        return redirect()->back()->with('success', 'Note modifiée avec succès');
    }

    public function destroy(Request $request, Prospect $prospect, Note $note): RedirectResponse
    {
        // Authorization: Ensure only the owner of the prospect can delete notes
        if ($request->user()->id !== $prospect->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // Ensure the note belongs to this prospect
        if ($note->prospect_id !== $prospect->id) {
            abort(404, 'Note not found for this prospect.');
        }

        $note->delete();

        return redirect()->back()->with('success', 'Note supprimée avec succès');
    }
}
