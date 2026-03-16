<?php

namespace App\Http\Controllers;

use App\Models\QuoteNumberSetting;
use App\Services\QuoteNumberService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuoteSettingsController extends Controller
{
    public function edit(Request $request, QuoteNumberService $service)
    {
        $settings = QuoteNumberSetting::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'prefix' => 'DEV',
                'digit_count' => 4,
                'include_year' => true,
                'last_number' => 0
            ]
        );

        return Inertia::render('Settings/Quotes', [
            'settings' => $settings,
            'preview' => $service->previewNext($request->user())
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'prefix' => 'nullable|string|max:10|regex:/^[a-zA-Z0-9\-_]*$/',
            'suffix' => 'nullable|string|max:10|regex:/^[a-zA-Z0-9\-_]*$/',
            'digit_count' => 'required|integer|min:1|max:10',
            'include_year' => 'required|boolean',
        ]);

        try {
            $settings = QuoteNumberSetting::updateOrCreate(
                ['user_id' => $request->user()->id],
                $validated
            );

            // Log the activity
            activity()
                ->causedBy($request->user())
                ->log('Updated quote numbering settings');

            return redirect()->back()->with('success', 'Paramètres de numérotation mis à jour.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour des paramètres. Veuillez réessayer.');
        }
    }

    public function destroy(Request $request)
    {
        QuoteNumberSetting::where('user_id', $request->user()->id)->delete();

        activity()
            ->causedBy($request->user())
            ->log('Reset quote numbering settings to defaults');

        return redirect()->back()->with('success', 'Paramètres réinitialisés.');
    }
}