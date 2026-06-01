<?php

namespace App\Services;

use App\DTOs\BrandingDTO;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Template;
use App\Models\User;

class TemplateService
{
    /**
     * Resolve branding for a given document (Invoice or Quote).
     */
    public function resolveBranding(Invoice|Quote $document): BrandingDTO
    {
        // 1. Prioritize snapshot if present (for immutability of generated documents)
        if ($document->branding_snapshot) {
            return BrandingDTO::fromArray($document->branding_snapshot);
        }

        // 2. Fallback to associated template
        if ($document->template_id) {
            $template = Template::find($document->template_id);
            if ($template) {
                return BrandingDTO::fromArray($template->toArray());
            }
        }

        // 3. Fallback to user's company profile
        return $this->getCompanyBranding($document->user);
    }

    /**
     * Get the default branding from the user's company profile.
     */
    public function getCompanyBranding(User $user): BrandingDTO
    {
        $profile = $user->companyProfile;
        
        if (!$profile) {
            return BrandingDTO::fromArray(config('branding.defaults', []));
        }

        return BrandingDTO::fromArray([
            'logo_path' => $profile->logo_path,
            'logo_size' => $profile->logo_size,
            'logo_position' => $profile->logo_position,
            'primary_color' => $profile->primary_color,
            'secondary_color' => $profile->secondary_color,
            'font_family' => $profile->font_family,
        ]);
    }

    /**
     * Set a template as default for a user.
     */
    public function setDefault(User $user, Template $template): void
    {
        if ($template->is_default) {
            return;
        }

        Template::where('user_id', $user->id)
            ->where('id', '!=', $template->id)
            ->update(['is_default' => false]);

        $template->update(['is_default' => true]);
    }
}
