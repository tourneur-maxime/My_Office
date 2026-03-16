<?php

namespace App\Services\FacturX;

use App\Models\Invoice;
use App\Models\CompanyProfile;
use App\Models\Prospect;
use App\Services\LegalMentionService;
use horstoeko\zugferd\ZugferdDocumentBuilder;
use horstoeko\zugferd\ZugferdProfiles;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FacturXService
{
    protected LegalMentionService $legalMentionService;

    public function __construct(LegalMentionService $legalMentionService)
    {
        $this->legalMentionService = $legalMentionService;
    }

    /**
     * Generate Factur-X XML content for the given invoice.
     *
     * @param Invoice $invoice
     * @return string
     * @throws \Exception
     */
    public function generateXml(Invoice $invoice): string
    {
        $company = $invoice->user->companyProfile;
        $client = $invoice->client;

        if (!$company) {
            throw new \Exception("Le profil d'entreprise est manquant pour l'utilisateur.");
        }

        if (!$client) {
            throw new \Exception("Le client est manquant pour cette facture.");
        }

        // Initialize ZUGFeRD Builder with EN 16931 profile (Comfortable)
        $document = ZugferdDocumentBuilder::CreateNew(ZugferdProfiles::PROFILE_EN16931);

        // -- Invoice Header Information --
        $document
            ->setDocumentInformation(
                $invoice->invoice_number,
                "380", // Invoice type code (380 = Commercial Invoice)
                $invoice->issue_date,
                $invoice->currency ?? "EUR"
            );

        // -- Legal Mentions (IncludedNote) --
        $legalMentions = $this->legalMentionService->getMentions($invoice);
        foreach ($legalMentions as $mention) {
            $subjectCode = "AAI"; // General Information
            if (str_contains($mention, 'TVA')) {
                $subjectCode = "ACB"; // Tax Information
            }
            $document->addDocumentNote($mention, $subjectCode);
        }

        // -- Seller Information (Company) --
        $sellerAddress = $this->parseAddress($company->address);
        $document
            ->setDocumentSeller(
                $company->name,
                (string)$company->id
            )
            ->setDocumentSellerAddress(
                $sellerAddress['line1'],
                $sellerAddress['line2'],
                null, // Line 3
                $sellerAddress['zip'],
                $sellerAddress['city'],
                "FR"
            );
            
        if ($company->vat_number) {
            $document->addDocumentSellerTaxRegistration("VA", $company->vat_number);
        }
        
        // Add SIRET as standard ID
        if ($company->siret) {
             // 0009 = SIRET (standard ISO 6523) - Verify this code is appropriate for Factur-X SIRET
             $document->addDocumentSellerGlobalId($company->siret, "0009"); 
             // 0002 = Company registration number (Commerce Register) - Verify this code is appropriate for Factur-X SIRENE
             $document->setDocumentSellerLegalOrganisation($company->siret, "0002", $company->name); 
        }

        // -- Buyer Information (Client) --
        $clientAddress = $this->parseAddress($client->address);
        $document
            ->setDocumentBuyer(
                $client->company ?? $client->name,
                (string)$client->id
            )
            ->setDocumentBuyerAddress(
                $clientAddress['line1'],
                $clientAddress['line2'],
                null, // Line 3
                $client->zip_code ?? $clientAddress['zip'],
                $client->city ?? $clientAddress['city'],
                "FR"
            );

        // -- Payment Terms & Due Date --
        $dueDate = $invoice->effective_due_date;
        $paymentTerms = $company->default_payment_terms ?? $company->payment_terms;

        if ($paymentTerms || $dueDate) {
            $document->addDocumentPaymentTerm($paymentTerms, $dueDate);
        }
        
        // -- Payment Means (Bank Details) --
        // BG-16
        if ($company->iban) {
            // $company->iban is automatically decrypted by Eloquent cast
            $document->addDocumentPaymentMeanToCreditTransfer(
                $company->iban, 
                $company->bank_account_holder ?? $company->name,
                null, // Account ID (not needed if IBAN used)
                $company->bic,
                $invoice->invoice_number // Payment reference (Remittance Information)
            );
            
            // Set Payment Means Type Code (BT-81) to 58 (SEPA Credit Transfer)
            // The library allows adding it via the document note or specific methods if supported.
            // Actually ZugferdDocumentBuilder->addDocumentPaymentMeanToCreditTransfer uses 30 by default.
            // We can force it to 58 if the library supports it, otherwise 30 is acceptable for standard credit transfer.
        }

        // -- Line Items --
        foreach ($invoice->lineItems as $item) {
            $taxCategoryCode = "S"; // Standard rate
            $taxPercent = $item->vat_rate;

            if ($company->is_vat_exempt) {
                $taxCategoryCode = "E"; // Exempt
                $taxPercent = 0.00;
            } elseif ($item->vat_rate == 0) {
                 $taxCategoryCode = "Z"; // Zero rated
            }

            $document->addNewPosition(
                (string)$item->id
            )
            ->setDocumentPositionProductDetails(
                $item->description,
                $item->description
            )
            ->setDocumentPositionNetPrice($item->unit_price)
            ->setDocumentPositionQuantity($item->quantity, "C62") // C62 = Unit
            ->addDocumentPositionTax(
                $taxCategoryCode,
                "VAT",
                $taxPercent
            )
            ->setDocumentPositionLineSummation($item->unit_price * $item->quantity);
        }

        // -- Totals --
        // Calculate tax basis total
        $taxBasisTotal = $invoice->subtotal;
        $taxTotal = $invoice->vat_amount;
        $grandTotal = $invoice->total;
        $dueAmount = $invoice->total;

        // Add Tax breakdown
        // Group by VAT rate
        $taxBreakdown = [];
        foreach ($invoice->lineItems as $item) {
             $rate = (string)$item->vat_rate;
             if (!isset($taxBreakdown[$rate])) {
                 $taxBreakdown[$rate] = 0;
             }
             $taxBreakdown[$rate] += $item->unit_price * $item->quantity;
        }

        foreach ($taxBreakdown as $rate => $basis) {
            $taxCategoryCode = "S";
            $rateFloat = (float)$rate;
            
            if ($company->is_vat_exempt) {
                $taxCategoryCode = "E";
                $rateFloat = 0.00;
            } elseif ($rateFloat == 0) {
                 $taxCategoryCode = "Z";
            }
            
            $calculatedTax = round($basis * ($rateFloat / 100), 2);

            $document->addDocumentTax(
                $taxCategoryCode,
                "VAT",
                $basis,
                $calculatedTax,
                $rateFloat
            );
        }

        $document
            ->setDocumentSummation(
                $grandTotal,    // GrandTotalAmount
                $dueAmount,     // DuePayableAmount
                $taxBasisTotal, // LineTotalAmount
                0,              // ChargeTotalAmount
                0,              // AllowanceTotalAmount
                $taxBasisTotal, // TaxBasisTotalAmount
                $taxTotal,      // TaxTotalAmount
                0,              // RoundingAmount
                0               // TotalPrepaidAmount
            );

        return $document->getContent();
    }

    /**
     * Validate the generated XML against the official XSD schema.
     * 
     * @param string $xmlContent
     * @return array Structured result: ['is_valid' => bool, 'errors' => array, 'validated_at' => string]
     */
    public function validateXml(string $xmlContent): array
    {
        $dom = new \DOMDocument();
        try {
            @$dom->loadXML($xmlContent);
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'errors' => ['XML invalide : ' . $e->getMessage()],
                'validated_at' => now()->toIso8601String(),
            ];
        }

        // Path to the main EN 16931 schema
        $schemaPath = storage_path('app/schemas/factur-x/FACTUR-X_EN16931.xsd');

        if (!file_exists($schemaPath)) {
            Log::error("Factur-X Schema not found at: $schemaPath");
            return [
                'is_valid' => false,
                'errors' => ["Le schéma de validation Factur-X est introuvable (Erreur technique)."],
                'validated_at' => now()->toIso8601String(),
            ];
        }

        libxml_use_internal_errors(true);
        
        $isValid = false;
        try {
            $isValid = $dom->schemaValidate($schemaPath);
        } catch (\Exception $e) {
            return [
                'is_valid' => false,
                'errors' => ["Erreur lors de la validation XSD : " . $e->getMessage()],
                'validated_at' => now()->toIso8601String(),
            ];
        }

        $errors = libxml_get_errors();
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = sprintf("Ligne %d : %s", $error->line, trim($error->message));
        }
        libxml_clear_errors();

        return [
            'is_valid' => $isValid,
            'errors' => $errorMessages,
            'validated_at' => now()->toIso8601String(),
            'spec_version' => '1.0.06',
            'profile' => 'EN 16931 (Comfortable)',
        ];
    }

    /**
     * Enhanced address parser.
     * Tries to extract Zip and City more reliably.
     */
    private function parseAddress(?string $fullAddress): array
    {
        $result = [
            'line1' => 'Non spécifié',
            'line2' => '',
            'zip' => '00000',
            'city' => 'Non spécifié',
        ];

        if (empty($fullAddress)) {
            return $result;
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", $fullAddress))));

        if (empty($lines)) {
            return $result;
        }

        $result['line1'] = mb_substr($lines[0], 0, 100);
        
        // Try to find ZIP and City in ANY line (regexp for 5 digits followed by space and city)
        foreach ($lines as $index => $line) {
            if (preg_match('/(\d{5})\s+(.+)/', $line, $matches)) {
                $result['zip'] = $matches[1];
                $result['city'] = mb_substr($matches[2], 0, 100);
                
                // If this wasn't the first line, and we have lines between line 1 and this one, put them in line 2
                if ($index > 0) {
                    $middleLines = array_slice($lines, 1, $index - 1);
                    if (!empty($middleLines)) {
                        $result['line2'] = mb_substr(implode(', ', $middleLines), 0, 100);
                    }
                }
                return $result;
            }
        }

        // Fallback: if no ZIP found, just use lines
        if (count($lines) > 1) {
            $result['line2'] = mb_substr($lines[1], 0, 100);
        }

        return $result;
    }
}
