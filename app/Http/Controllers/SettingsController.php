<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show the branding settings page.
     */
    public function branding(Request $request): Response
    {
        return Inertia::render('Settings/Branding', [
            'companyProfile' => $request->user()->companyProfile,
        ]);
    }

    /**
     * Update branding settings (colors and font).
     */
    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_family' => ['nullable', 'string', Rule::in(array_keys(config('branding.fonts', [])))],
            'logo_size' => ['nullable', 'integer', 'min:50', 'max:300'],
            'logo_position' => ['nullable', 'string', 'in:left,center,right'],
        ]);

        $companyProfile = $request->user()->companyProfile;

        if (! $companyProfile) {
            return redirect()->back()->with('error', 'Veuillez d\'abord configurer votre profil d\'entreprise.');
        }

        $companyProfile->update($validated);

        return redirect()->back()->with('success', 'Paramètres de marque mis à jour.');
    }

    /**
     * Reset branding settings to defaults.
     */
    public function resetBranding(Request $request): RedirectResponse
    {
        $companyProfile = $request->user()->companyProfile;

        if (! $companyProfile) {
            return redirect()->back()->with('error', 'Aucun profil d\'entreprise a reinitialiser.');
        }

        // Delete the logo file if it exists
        if ($companyProfile->logo_path && Storage::disk('public')->exists($companyProfile->logo_path)) {
            Storage::disk('public')->delete($companyProfile->logo_path);
        }

        // Reset to default values from config
        $defaults = config('branding.defaults');
        $companyProfile->update($defaults);

        return redirect()->back()->with('success', 'Parametres de marque reinitialises aux valeurs par defaut.');
    }
}
