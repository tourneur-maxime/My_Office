<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLogoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class LogoController extends Controller
{
    /**
     * Store a newly uploaded logo.
     */
    public function store(StoreLogoRequest $request): RedirectResponse
    {
        $user = $request->user();
        $companyProfile = $user->companyProfile;

        if (! $companyProfile) {
            return redirect()->back()->with('error', 'Veuillez d\'abord configurer votre profil d\'entreprise.');
        }

        // Delete old logo if exists
        if ($companyProfile->logo_path && Storage::disk('public')->exists($companyProfile->logo_path)) {
            Storage::disk('public')->delete($companyProfile->logo_path);
        }

        $file = $request->file('logo');
        $extension = $file->getClientOriginalExtension();

        // Generate unique filename
        $filename = 'logo-'.$user->id.'-'.Str::random(10).'.webp';
        $path = 'logos/'.$filename;

        // Process image with Intervention Image
        if (strtolower($extension) === 'svg') {
            // For SVG, just store directly without processing
            $svgFilename = 'logo-'.$user->id.'-'.Str::random(10).'.svg';
            $svgPath = 'logos/'.$svgFilename;
            Storage::disk('public')->put($svgPath, $file->get());
            $companyProfile->update(['logo_path' => $svgPath]);
        } else {
            // Process raster images: resize, convert to WebP
            $image = Image::read($file);

            // Resize to max 500x500 while maintaining aspect ratio
            $image->scaleDown(500, 500);

            // Encode to WebP with 85% quality
            $encoded = $image->toWebp(85);

            Storage::disk('public')->put($path, (string) $encoded);
            $companyProfile->update(['logo_path' => $path]);
        }

        return redirect()->back()->with('success', 'Logo telecharge avec succes.');
    }

    /**
     * Remove the logo from storage.
     */
    public function destroy(): RedirectResponse
    {
        $user = request()->user();
        $companyProfile = $user->companyProfile;

        if (! $companyProfile || ! $companyProfile->logo_path) {
            return redirect()->back()->with('error', 'Aucun logo a supprimer.');
        }

        // Delete the file from storage
        if (Storage::disk('public')->exists($companyProfile->logo_path)) {
            Storage::disk('public')->delete($companyProfile->logo_path);
        }

        // Clear the logo_path in database
        $companyProfile->update(['logo_path' => null]);

        return redirect()->back()->with('success', 'Logo supprime avec succes.');
    }
}
