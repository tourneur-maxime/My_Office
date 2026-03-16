<?php

namespace App\Services;

use App\Models\Quote;
use App\DTOs\BrandingDTO;
use TCPDF;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    /**
     * Generate a PDF for a quote.
     */
    public function generate(Quote $quote, ?BrandingDTO $branding = null): string
    {
        $company = $quote->user->companyProfile;
        $client = $quote->client;

        if (!$branding) {
            $templateService = app(TemplateService::class);
            $branding = $templateService->resolveBranding($quote);
        }

        // Determine Colors and Fonts
        $colors = $this->determineColors($branding);
        $fontName = $this->determineFont($branding->fontFamily ?? 'sans-serif');
        $primaryColor = $colors['primary'];
        $secondaryColor = $colors['secondary'];

        // Custom TCPDF class
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Document information
        $pdf->SetCreator('my_office');
        $pdf->SetAuthor($company->name);
        $pdf->SetTitle('Devis '.$quote->quote_number);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Set font
        $pdf->SetFont($fontName, '', 10);
        $pdf->AddPage();

        if ($quote->layout_configuration && is_array($quote->layout_configuration)) {
            $this->renderDynamicLayout($pdf, $quote, $branding, $primaryColor, $secondaryColor, $fontName);
            $pdf->SetY(260); 
        } else {
            // Max logo width
            $MAX_LOGO_WIDTH_MM = 80;

            // Logo & Company Info
            if ($branding->logoPath && Storage::disk('public')->exists($branding->logoPath)) {
            $logoPath = Storage::disk('public')->path($branding->logoPath);
            $desiredLogoWidthMm = min(($branding->logoSize ?? 100) / 3.78, $MAX_LOGO_WIDTH_MM);
            
            $x = 15;
            if ($branding->logoPosition === 'center') {
                $x = (210 - $desiredLogoWidthMm) / 2;
            } elseif ($branding->logoPosition === 'right') {
                $x = 210 - 15 - $desiredLogoWidthMm;
            }

            $pdf->Image($logoPath, $x, 15, $desiredLogoWidthMm);
        }

        $pdf->SetXY(15, 40);
        $pdf->SetFont($fontName, 'B', 14);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(0, 10, $company->name, 0, 1, 'L');

        $pdf->SetFont($fontName, '', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->MultiCell(80, 5, $company->address, 0, 'L');
        $pdf->Cell(0, 5, 'SIRET: '.$company->siret, 0, 1, 'L');

        // Quote Info
        $pdf->SetXY(110, 15);
        $pdf->SetFont($fontName, 'B', 20);
        $pdf->SetTextColor(200, 200, 200);
        $pdf->Cell(85, 10, 'DEVIS', 0, 1, 'R');

        $pdf->SetFont($fontName, 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(180, 5, 'N° '.($quote->quote_number ?? 'BROUILLON'), 0, 1, 'R');
        $pdf->SetFont($fontName, '', 9);
        $pdf->Cell(180, 5, 'Date: '.($quote->created_at ? $quote->created_at->format('d/m/Y') : now()->format('d/m/Y')), 0, 1, 'R');
        if ($quote->expires_at) {
            $pdf->Cell(180, 5, 'Valide jusqu\'au: '.$quote->expires_at->format('d/m/Y'), 0, 1, 'R');
        }

        // Client Info
        $pdf->SetXY(110, 45);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetDrawColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
        $pdf->RoundedRect(110, 45, 85, 30, 2, '1111', 'DF');
        $pdf->SetXY(115, 47);
        $pdf->SetFont($fontName, 'B', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, 'DESTINATAIRE', 0, 1, 'L');
        $pdf->SetXY(115, 52);
        $pdf->SetFont($fontName, 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 5, $client->company ?? $client->name, 0, 1, 'L');
        $pdf->SetXY(115, 57);
        $pdf->SetFont($fontName, '', 9);
        if ($client->company) {
            $pdf->Cell(0, 5, $client->name, 0, 1, 'L');
            $pdf->SetX(115);
        }
        $pdf->MultiCell(75, 5, $client->address, 0, 'L');

        // Table Header
        $pdf->SetXY(15, 85);
        $pdf->SetFillColor($secondaryColor[0], $secondaryColor[1], $secondaryColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont($fontName, 'B', 9);
        $pdf->Cell(90, 8, 'Description', 1, 0, 'L', true);
        $pdf->Cell(20, 8, 'Qté', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'P.U. HT', 1, 0, 'R', true);
        $pdf->Cell(20, 8, 'TVA', 1, 0, 'R', true);
        $pdf->Cell(25, 8, 'Total HT', 1, 1, 'R', true);

        // Table Rows
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont($fontName, '', 9);
        $pdf->SetFillColor(255, 255, 255);

        foreach ($quote->lineItems as $item) {
            $h = $pdf->getStringHeight(90, $item->description);
            $h = max($h, 8);

            if ($pdf->GetY() + $h > 250) {
                $pdf->AddPage();
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

        // Totals
        $pdf->Ln(5);
        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'Sous-total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->subtotal, 2, ',', ' ').' €', 0, 1, 'R');

        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'TVA', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->vat_amount, 2, ',', ' ').' €', 0, 1, 'R');

        $pdf->SetX(130);
        $pdf->SetFont($fontName, 'B', 11);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(35, 10, 'Total TTC', 'T', 0, 'L');
        $pdf->Cell(30, 10, number_format($quote->total, 2, ',', ' ').' €', 'T', 1, 'R');
        }

        // Footer
        $pdf->SetY(260);
        $pdf->SetFont($fontName, 'I', 7);
        $pdf->SetTextColor(150, 150, 150);
        $text = '';
        if ($company->is_vat_exempt) {
            $text .= "TVA non applicable, art. 293 B du CGI\n";
        }
        $text .= "Micro-entrepreneur selon l'article L. 123-1-1 du code de commerce";
        $pdf->MultiCell(0, 5, $text, 0, 'C');

        return $pdf->Output('', 'S');
    }

    protected function determineFont(string $userFont): string
    {
        $userFontLower = strtolower($userFont);
        $map = [
            'monospace' => 'dejavusansmono',
            'serif' => 'dejavuserif',
            'georgia' => 'dejavuserif',
            'arial' => 'dejavusans',
            'verdana' => 'dejavusans',
            'sans-serif' => 'dejavusans',
        ];

        foreach ($map as $key => $value) {
            if (str_contains($userFontLower, $key)) {
                return $value;
            }
        }

        return 'dejavusans';
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

    protected function renderDynamicLayout($pdf, $quote, $branding, $primaryColor, $secondaryColor, $fontName)
    {
        foreach ($quote->layout_configuration as $block) {
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
                    $this->renderLineItemsBlock($pdf, $quote, $secondaryColor, $fontName);
                    break;
                case 'TotalsBlock':
                    $this->renderTotalsBlock($pdf, $quote, $primaryColor, $fontName);
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
            $pdf->SetY($pdf->GetY() + $widthMm / 2);
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

    protected function renderLineItemsBlock($pdf, $quote, $secondaryColor, $fontName)
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
        foreach ($quote->lineItems as $item) {
            $h = max($pdf->getStringHeight(90, $item->description), 8);
            $pdf->MultiCell(90, $h, $item->description, 1, 'L', false, 0);
            $pdf->Cell(20, $h, $item->quantity, 1, 0, 'C');
            $pdf->Cell(25, $h, number_format($item->unit_price, 2, ',', ' ').' €', 1, 0, 'R');
            $pdf->Cell(20, $h, $item->vat_rate.'%', 1, 0, 'R');
            $pdf->Cell(25, $h, number_format($item->quantity * $item->unit_price, 2, ',', ' ').' €', 1, 1, 'R');
        }
    }

    protected function renderTotalsBlock($pdf, $quote, $primaryColor, $fontName)
    {
        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'Sous-total HT', 0, 0, 'L');
        $pdf->Cell(30, 7, number_format($quote->subtotal, 2, ',', ' ').' €', 0, 1, 'R');
        $pdf->SetX(130);
        $pdf->Cell(35, 7, 'Total TTC', 'T', 0, 'L');
        $pdf->SetFont($fontName, 'B', 11);
        $pdf->SetTextColor($primaryColor[0], $primaryColor[1], $primaryColor[2]);
        $pdf->Cell(30, 10, number_format($quote->total, 2, ',', ' ').' €', 'T', 1, 'R');
    }
}
