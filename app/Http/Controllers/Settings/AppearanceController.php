<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AppearanceController extends Controller
{
    /**
     * Display the appearance settings page.
     */
    public function edit(Request $request)
    {
        return Inertia::render('Settings/Appearance', [
            'preferences' => $request->user()->theme_preferences ?? $this->getDefaultPreferences(),
        ]);
    }

    /**
     * Update the appearance settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:light,dark,system'],
            'primary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'gray_shade' => ['required', 'string', 'in:slate,gray,zinc,neutral,stone'],
            'radius' => ['required', 'string', 'in:none,sm,md,lg,xl,full'],
            'card_border_style' => ['required', 'string', 'in:subtle,medium,strong'],
        ]);

        $request->user()->update([
            'theme_preferences' => $validated,
        ]);

        // Check response type in order: Inertia > AJAX/JSON > Standard redirect
        if ($request->header('X-Inertia')) {
            return Redirect::route('settings.appearance')->with('success', 'Préférences de thème mises à jour.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Préférences de thème mises à jour.',
                'preferences' => $validated,
            ]);
        }

        return Redirect::route('settings.appearance')->with('success', 'Préférences de thème mises à jour.');
    }

    private function getDefaultPreferences()
    {
        return [
            'mode' => 'system',
            'primary_color' => '#0071E3', // Apple Blue
            'gray_shade' => 'slate',
            'radius' => 'lg',
            'card_border_style' => 'subtle',
        ];
    }
}
