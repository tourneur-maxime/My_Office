<?php

namespace App\Services;

use App\Models\Invoice;

class LegalMentionService
{
    /**
     * Get array of legal mentions based on the invoice's company profile.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function getMentions(Invoice $invoice): array
    {
        $mentions = [];
        $companyProfile = $invoice->user->companyProfile;

        if (!$companyProfile) {
            return $mentions;
        }

        // Late payment penalty is mandatory for all B2B invoices.
        $mentions[] = !empty($companyProfile->late_payment_penalty_rate)
            ? $companyProfile->late_payment_penalty_rate
            : config('legal.late_penalty', 'Indemnité forfaitaire pour frais de recouvrement : 40€. Taux des pénalités de retard : 10%.');

        if ($companyProfile->is_vat_exempt) {
            $mentions[] = config('legal.vat_exempt', 'TVA non applicable, art. 293 B du CGI');
            
            // Assuming "is_vat_exempt" usually correlates with Auto-Entrepreneur / Micro-Enterprise status
            // Ideally we'd have a specific legal_form field, but for this story we check VAT status
            $mentions[] = config('legal.micro_entrepreneur', 'Micro-entrepreneur selon l\'article L. 123-1-1 du code de commerce');
        }

        return $mentions;
    }

    /**
     * Validate that all mandatory fields for an invoice are present in the company profile.
     *
     * @param \App\Models\CompanyProfile $profile
     * @return array List of missing field labels
     */
    public function validateMandatoryFields($profile): array
    {
        $mandatoryFields = [
            'name' => 'Raison sociale de l\'entreprise',
            'address' => 'Adresse',
            'zip_code' => 'Code postal',
            'city' => 'Ville',
            'siret' => 'Numéro SIRET',
        ];

        $missing = [];
        foreach ($mandatoryFields as $field => $label) {
            if (empty($profile->$field)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }
}