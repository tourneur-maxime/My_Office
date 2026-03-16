<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyProfileController extends Controller
{
    /**
     * Show the company profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/CompanyProfile', [
            'companyProfile' => $request->user()->companyProfile,
        ]);
    }

    /**
     * Update the company profile settings.
     */
    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $companyProfile = $request->user()->companyProfile;

        if (!$companyProfile) {
            $request->user()->companyProfile()->create($validated);
        } else {
            $companyProfile->update($validated);
        }

        return redirect()->back()->with('success', 'Profil d\'entreprise mis à jour avec succès.');
    }
}