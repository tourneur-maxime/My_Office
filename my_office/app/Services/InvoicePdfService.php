<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\CompanyProfile;
use App\DTOs\BrandingDTO;
use TCPDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoicePdfService
{
    protected LegalMentionService $legalMentionService;

    public function __construct(LegalMentionService $legalMentionService)
    {
        $this->legalMentionService = $legalMentionService;
    }

    /**
     * Generate a PDF for an invoice, optionally embedding Factur-X XML.
     *
     * @param Invoice $invoice
     * @param string|null $facturXXml Content of factur-x.xml (if provided, PDF/A-3 is generated)
     * @param BrandingDTO|null $branding
     * @return string PDF content
     */
    public function generate(Invoice $invoice, ?string $facturXXml = null, ?BrandingDTO $branding = null): string
    {
        $company = $invoice->user->companyProfile;
        $client = $invoice->client;

        // If no branding provided, get it from TemplateService or defaults
        if (!$branding) {
            $templateService = app(TemplateService::class);
            $branding = $templateService->resolveBranding($invoice);
        }

        // Determine Colors and Fonts
        $colors = $this->determineColors($branding);
        $fontName = $this->determineFont($branding->fontFamily ?? 'sans-serif');
        $primaryColor = $colors['primary'];
        $secondaryColor = $colors['secondary'];

        // Custom TCPDF class could be used, but standard is fine for now
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Max logo width to prevent obscuring content, in millimeters
        $MAX_LOGO_WIDTH_MM = 80;

        // PDF/A-3 Configuration
        if ($facturXXml) {
             // NOTE: Strict PDF/A-3b compliance requires SetOutputIntent which is missing in standard TCPDF.
             // We proceed with Hybrid PDF (standard PDF + XML attachment) which allows data extraction.
             
             $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
             $pdf->setFontSubsetting(true);
        }

        // Document information
        $pdf->SetCreator('my_office');
        $pdf->SetAuthor($company->name);
        $pdf->SetTitle('Facture '.$invoice->invoice_number);
        $pdf->SetSubject('Facture pour '.$client->name);
        $pdf->SetKeywords('Factur-X, Invoice, Facture');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Font Handling
        $pdf->SetFont($fontName, '', 10);
        $pdf->AddPage();

        if ($invoice->layout_configuration && is_array($invoice->layout_configuration)) {
            $this->renderDynamicLayout($pdf, $invoice, $branding, $primaryColor, $secondaryColor, $fontName);
            $pdf->SetY(260); // Skip legacy rendering by jumping to footer
        } else {
            // Logo
            if ($branding->logoPath && Storage::disk('public')->exists($branding->logoPath)) {
            $logoPath = Storage::disk('public')->path($branding->logoPath);
            // Calculate desired logo width in mm, capped by MAX_LOGO_WIDTH_MM
            $desiredLogoWidthMm = min(($branding->logoSize ?? 100) / 3.78, $MAX_LOGO_WIDTH_MM);
            
            // Align based on position
            $x = 15;
            if ($branding->logoPosition === 'center') {
                $x = (210 - $desiredLogoWidthMm) / 2;
            } elseif ($branding->logoPosition === 'right') {
                $x = 210 - 15 - $desiredLogoWidthMm;
            }

            $pdf->Image($logoPath, $x, 15, $desiredLogoWidthMm);
        }

        // Seller Info
        $pdf->SetXY(15, 40);
        $pdf->SetFont($fontName, 'B', 14);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');

        $pdf->SetFont($fontName, '', 9);
        $pdf->SetTextColor(80, 80, 80);
        if ($company->legal_form) {
            $legalLine = $company->legal_form;
            if ($company->share_capital) {
                $legalLine .= ' au capital de '.number_format($company->share_capital, 0, ',', ' ').' €';
            }
            $pdf->Cell(0, 5, $legalLine, 0, 1, 'L');
        }
        $pdf->MultiCell(80, 5, $company->address, 0, 'L');
        if ($company->zip_code || $company->city) {
            $pdf->Cell(0, 5, trim(($company->zip_code ?? '').' '.($company->city ?? '')), 0, 1, 'L');
        }
        $pdf->Cell(0, 5, 'SIRET: '.$company->siret, 0, 1, 'L');
        if ($company->rcs_number) {
            $pdf->Cell(0, 5, 'RCS: '.$company->rcs_number, 0, 1, 'L');
        }
        if ($company->vat_number) {
             $pdf->Cell(0, 5, 'TVA Intracom.: '.$company->vat_number, 0, 1, 'L');
        }

        // Invoice Info Header
        $pdf->SetXY(110, 15);
        $pdf->SetFont($fontName, 'B', 20);
        $pdf->SetTextColor(200, 200, 200); // Light gray for "FACTURE" label
        $docLabel = $invoice->type === InvoiceType::Avoir ? 'AVOIR' : 'FACTURE';
        $pdf->Cell(85, 10, $docLabel, 0, 1, 'R');

        $pdf->SetFont($fontName, 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(180, 5, 'N° '.($invoice->invoice_number ?? 'BROUILLON'), 0, 1, 'R');
        $pdf->SetFont($fontName, '', 9);
        $pdf->Cell(180, 5, 'Date: ' . ($invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : date('d/m/Y')), 0, 1, 'R');
        
        // Buyer Info
        $pdf->SetXY(110, 45);
        $pdf->SetFillColor(245, 245, 245);
        // Use Secondary Color for border
        $pdf->SetDrawColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
        $pdf->RoundedRect(110, 45, 85, 30, 2, '1111', 'DF');
        $pdf->SetXY(115, 47);
        $pdf->SetFont($fontName, 'B', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, 'DESTINATAIRE', 0, 1, 'L');
        $pdf->SetXY(115, 52);
        $pdf->SetFont($fontName, 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $buyerName = $client->company ?? $client->name;
        $pdf->Cell(0, 5, $buyerName, 0, 1, 'L');
        $pdf->SetXY(115, 57);
        $pdf->SetFont($fontName, '', 9);
        $pdf->MultiCell(75, 5, $client->address, 0, 'L');

        // Table Header
        $pdf->SetXY(15, 85);
        // Use Secondary Color for Table Header Background
        $pdf->SetFillColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($fontName, 'B', 9);
        // Reset Draw Color to black or default
        $pdf->SetDrawColor(200, 200, 200); 

        $pdf->Cell(90, 8, 'Description', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Qté', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'P.U. HT', 1, 0, 'R', true);
        $pdf->Cell(20, 8, 'TVA', 1, 0, 'R', true);
        $pdf->Cell(25, 8, 'Total HT', 1, 1, 'R', true);

        // Table Rows
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($fontName, '', 9);
        $pdf->SetFillColor(255, 255, 255);

        foreach ($invoice->lineItems as $item) {
            $h = $pdf->getStringHeight(90, $item->description);
            $h = max($h, 8);

            if ($pdf->GetY() + $h > 250) {
                $pdf->AddPage();
                // Re-draw header
                $pdf->SetFillColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont($fontName, 'B', 9);
                $pdf->Cell(90, 8, 'Description', 1, 0, 'L', true);
                $pdf->Cell(20, 8, 'Qté', 1, 0, 'C', true);
                $pdf->Cell(25, 8, 'P.U. HT', 1, 0, 'R', true);
                $pdf->Cell(20, 8, 'TVA', 1, 0, 'R', true);
                $pdf->Cell(25, 8, 'Total HT', 1, 1, 'R', true);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont($fontName, '', 9);
            }

            $pdf->MultiCell(90, $h, $item->description, 1, 'L', false, 0);
            $pdf->Cell(20, $h, $item->quantity, 1, 0, 'C');
            $pdf->Cell(25, $h, number_format($item->unit_price, 2, ',', ' ').' €', 1, 0, 'R');
            $pdf->Cell(20, $h, $item->vat_rate.'%', 1, 0, 'R');
            $pdf->Cell(25, $h, number_format($item->quantity * $item->unit_price, 2, ',', ' ').' €', 1, 1, 'R');
        }

        // Layout: Payment Details (Left) | Totals (Right)
        $pdf->Ln(5);
        
        // Ensure we have enough space for payment details + totals
        if ($pdf->GetY() > 220) {
            $pdf->AddPage();
        }
        
        $yAfterTable = $pdf->GetY();
        
        // Right side: Totals
        $pdf->SetXY(130, $yAfterTable);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(35, 7, 'Sous-total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($invoice->subtotal, 2, ',', ' ').' €', 0, 1, 'R');

        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'TVA', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($invoice->vat_amount, 2, ',', ' ').' €', 0, 1, 'R');

        $pdf->SetX(130);
        $pdf->SetFont($fontName, 'B', 11);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(35, 10, 'Total TTC', 'T', 0, 'L');
        $pdf->Cell(30, 10, number_format($invoice->total, 2, ',', ' ').' €', 'T', 1, 'R');

        // Left side: Payment Details
        $pdf->SetXY(15, $yAfterTable);
        $pdf->SetFont($fontName, 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(100, 7, 'Informations de paiement', 0, 1, 'L');
        $pdf->SetFont($fontName, '', 9);
        $pdf->SetTextColor(50, 50, 50);

        // Bank details
        if ($company->bank_name || $company->iban) {
            if ($company->bank_name) {
                $pdf->Cell(100, 5, 'Banque: ' . $company->bank_name, 0, 1, 'L');
            }
            if ($company->bank_account_holder) {
                $pdf->Cell(100, 5, 'Titulaire: ' . $company->bank_account_holder, 0, 1, 'L');
            }
            if ($company->iban) {
                // Format IBAN in blocks of 4
                $formattedIban = implode(' ', str_split($company->iban, 4));
                $pdf->Cell(100, 5, 'IBAN: ' . $formattedIban, 0, 1, 'L');
            }
            if ($company->bic) {
                $pdf->Cell(100, 5, 'BIC: ' . $company->bic, 0, 1, 'L');
            }
        }
        
        $pdf->Ln(2);
        
        // Payment Terms
        $dueDate = $invoice->effective_due_date;
        
        if ($dueDate) {
            $pdf->Cell(100, 5, 'Date d\'échéance: ' . $dueDate->format('d/m/Y'), 0, 1, 'L');
        }
        
        $paymentTerms = $company->default_payment_terms ?? $company->payment_terms;
                    if ($paymentTerms) {
                        $pdf->MultiCell(100, 5, 'Conditions: ' . $paymentTerms, 0, 'L');
                    }
                }
        
                // Footer
                $pdf->SetY(260);        $pdf->SetFont($fontName, 'I', 7);
        $pdf->SetTextColor(150, 150, 150);
        
        $legalMentions = $this->legalMentionService->getMentions($invoice);
        $text = implode("\n", $legalMentions);
        
        if ($company->custom_legal_mentions) {
            $text .= "\n" . $company->custom_legal_mentions;
        }
        $pdf->MultiCell(0, 5, $text, 0, 'C');

        if ($facturXXml) {
            $this->attachFacturX($pdf, $facturXXml);
        }

        return $pdf->Output('', 'S');
    }

    private function attachFacturX(TCPDF $pdf, string $xmlContent)
    {
        $pdf->Annotation(0, 0, 0, 0, 'Factur-X XML', array(
            'Subtype' => 'FileAttachment',
            'Name' => 'PushPin',
            'FS' => 'factur-x.xml',
            'Contents' => $xmlContent
        ));

        $xmp = $this->generateXmpMetadata();
        $pdf->setExtraXMP($xmp);
    }

    private function generateXmpMetadata(): string
    {
        return <<<EOD
<rdf:Description rdf:about="" xmlns:fx="urn:factur-x:pdf1.0:xsd:fx:1.0">
  <fx:DocumentType>INVOICE</fx:DocumentType>
  <fx:DocumentFileName>factur-x.xml</fx:DocumentFileName>
  <fx:DocumentLevel>EN 16931</fx:DocumentLevel>
  <fx:Version>1.0</fx:Version>
  <fx:ConformanceLevel>EN 16931</fx:ConformanceLevel>
</rdf:Description>
EOD;
    }

    protected function determineFont(string $userFont): string
    {
        return \App\Support\FontMapper::resolve($userFont);
    }

    protected function determineColors(BrandingDTO $branding): array
    {
        $primary = $this->hexToRgb($branding->primaryColor ?? '#3B82F6');
        $secondary = $this->hexToRgb($branding->secondaryColor ?? '#1E40AF');

        return [
            'primary' => $primary,
            'secondary' => $secondary,
        ];
    }

    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1).substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1).substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1).substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return [$r, $g, $b];
    }

    protected function renderDynamicLayout($pdf, $invoice, $branding, $primaryColor, $secondaryColor, $fontName)
    {
        foreach ($invoice->layout_configuration as $block) {
            $type = $block['type'] ?? '';
            $content = $block['content'] ?? [];

            switch ($type) {
                case 'LogoBlock':
                    $this->renderLogoBlock($pdf, $content, $branding);
                    break;
                case 'AddressBlock':
                    $this->renderAddressBlock($pdf, $content, $fontName);
                    break;
                case 'LineItemsBlock':
                    $this->renderLineItemsBlock($pdf, $invoice, $secondaryColor, $fontName);
                    break;
                case 'TotalsBlock':
                    $this->renderTotalsBlock($pdf, $invoice, $primaryColor, $fontName);
                    break;
            }
            $pdf->Ln(10);
        }
    }

    protected function renderLogoBlock($pdf, $content, $branding)
    {
        $logoPath = $branding->logoPath;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $fullPath = Storage::disk('public')->path($logoPath);
            $size = $content['size'] ?? $branding->logoSize ?? 100;
            $pos = $content['position'] ?? $branding->logoPosition ?? 'left';
            $widthMm = $size / 3.78;

            $x = 15;
            if ($pos === 'center') $x = (210 - $widthMm) / 2;
            elseif ($pos === 'right') $x = 210 - 15 - $widthMm;

            $pdf->Image($fullPath, $x, $pdf->GetY(), $widthMm);
            $pdf->SetY($pdf->GetY() + $widthMm / 2); // Approximation for flow
        }
    }

    protected function renderAddressBlock($pdf, $content, $fontName)
    {
        $pdf->SetFont($fontName, 'B', 8);
        $pdf->SetTextColor(150, 150, 150);
        if (!empty($content['label'])) {
            $pdf->Cell(0, 5, strtoupper($content['label']), 0, 1, 'L');
        }
        $pdf->SetFont($fontName, '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->MultiCell(0, 5, $content['address'] ?? '', 0, 'L');
    }

    protected function renderLineItemsBlock($pdf, $invoice, $secondaryColor, $fontName)
    {
        $pdf->SetFillColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($fontName, 'B', 9);
        $pdf->Cell(90, 8, 'Description', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Qté', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'P.U. HT', 1, 0, 'R', true);
        $pdf->Cell(20, 8, 'TVA', 1, 0, 'R', true);
        $pdf->Cell(25, 8, 'Total HT', 1, 1, 'R', true);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($fontName, '', 9);
        foreach ($invoice->lineItems as $item) {
            $h = max($pdf->getStringHeight(90, $item->description), 8);
            $pdf->MultiCell(90, $h, $item->description, 1, 'L', false, 0);
            $pdf->Cell(20, $h, $item->quantity, 1, 0, 'C');
            $pdf->Cell(25, $h, number_format($item->unit_price, 2, ',', ' ').' €', 1, 0, 'R');
            $pdf->Cell(20, $h, $item->vat_rate.'%', 1, 0, 'R');
            $pdf->Cell(25, $h, number_format($item->quantity * $item->unit_price, 2, ',', ' ').' €', 1, 1, 'R');
        }
    }

    protected function renderTotalsBlock($pdf, $invoice, $primaryColor, $fontName)
    {
        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'Sous-total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($invoice->subtotal, 2, ',', ' ').' €', 0, 1, 'R');
        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'Total TTC', 'T', 0, 'L');
        $pdf->SetFont($fontName, 'B', 11);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(30, 10, number_format($invoice->total, 2, ',', ' ').' €', 'T', 1, 'R');
    }
}
