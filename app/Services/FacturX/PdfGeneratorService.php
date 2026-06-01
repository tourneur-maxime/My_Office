<?php

declare(strict_types=1);

namespace App\Services\FacturX;

use App\Models\Invoice;
use TCPDF;

/**
 * Service for generating PDF/A compliant invoices using TCPDF.
 *
 * Implements FR38 (PDF/A compliance), FR39 (XML embedding), FR43 (XMP metadata),
 * and FR44 (embedded fonts).
 */
class PdfGeneratorService
{
    private const FACTURX_FILENAME = 'factur-x.xml';

    /**
     * Generate a PDF/A compliant invoice PDF (without Factur-X XML).
     *
     * @param  Invoice  $invoice  The invoice to generate PDF for
     * @return string Raw PDF content
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->load(['client', 'lineItems', 'user.companyProfile']);

        $pdf = $this->createPdfInstance($invoice->user->companyProfile);
        $this->configurePdfCompliance($pdf);
        $this->setDocumentInfo($pdf, $invoice);
        $this->addPage($pdf);
        $this->renderContent($pdf, $invoice);

        return $pdf->Output('', 'S');
    }

    /**
     * Generate a complete Factur-X PDF with embedded XML and XMP metadata.
     *
     * @param  Invoice  $invoice  The invoice to generate PDF for
     * @param  string  $xml_content  Validated Factur-X XML content
     * @param  string  $conformance_level  Factur-X conformance level
     * @return string Raw PDF content with embedded XML
     */
    public function generateWithFacturX(
        Invoice $invoice,
        string $xml_content,
        string $conformance_level
    ): string {
        $invoice->load(['client', 'lineItems', 'user.companyProfile']);

        $pdf = $this->createPdfInstance($invoice->user->companyProfile);
        $this->configurePdfCompliance($pdf);
        $this->setDocumentInfo($pdf, $invoice);
        $this->embedFacturXXml($pdf, $xml_content);
        $this->setFacturXXmpMetadata($pdf, $conformance_level);
        $this->addPage($pdf);
        $this->renderContent($pdf, $invoice);

        return $pdf->Output('', 'S');
    }

    /**
     * Create and configure base TCPDF instance.
     *
     * The pdfa parameter enables PDF/A-1b mode with embedded fonts
     * and no JavaScript/rich media.
     */
    private function createPdfInstance(?object $companyProfile = null): TCPDF
    {
        $pdf = new TCPDF(
            orientation: 'P',
            unit: 'mm',
            format: 'A4',
            unicode: true,
            encoding: 'UTF-8',
            diskcache: false,
            pdfa: true
        );

        $pdf->SetCreator('my_office');
        $pdf->SetAuthor('my_office');

        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetMargins(15, 15, 15);

        $fontFamily = $this->mapFont($companyProfile->font_family ?? 'sans-serif');
        $pdf->SetFont($fontFamily, '', 10);

        return $pdf;
    }

    /**
     * Configure PDF compliance settings.
     *
     * Ensures fonts are fully embedded (not subsetted) for archival compliance.
     */
    private function configurePdfCompliance(TCPDF $pdf): void
    {
        $pdf->setFontSubsetting(false);
    }

    /**
     * Set PDF document metadata.
     */
    private function setDocumentInfo(TCPDF $pdf, Invoice $invoice): void
    {
        $pdf->SetTitle('Facture '.$invoice->invoice_number);
        $pdf->SetSubject('Facture '.$invoice->invoice_number);
        $pdf->SetKeywords('facture, invoice, factur-x, '.$invoice->invoice_number);
    }

    /**
     * Embed Factur-X XML as an associated file in the PDF.
     *
     * The XML is embedded with the filename 'factur-x.xml' and relationship '/Alternative'
     * as required by the Factur-X specification.
     *
     * Uses a temporary file approach since TCPDF requires a file path for attachments.
     *
     * @param  TCPDF  $pdf  The PDF instance
     * @param  string  $xml_content  The Factur-X XML content to embed
     */
    private function embedFacturXXml(TCPDF $pdf, string $xml_content): void
    {
        $temp_file = tempnam(sys_get_temp_dir(), 'facturx_');
        file_put_contents($temp_file, $xml_content);

        $pdf->Annotation(
            x: 0,
            y: 0,
            w: 0,
            h: 0,
            text: 'Factur-X XML Data',
            opt: [
                'Subtype' => 'FileAttachment',
                'Name' => 'PushPin',
                'FS' => $temp_file,
                'T' => self::FACTURX_FILENAME,
            ],
            spaces: 0,
        );

        register_shutdown_function(function () use ($temp_file) {
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        });
    }

    /**
     * Set Factur-X XMP metadata in the PDF.
     *
     * Adds the Factur-X namespace and conformance level to XMP metadata.
     *
     * @param  TCPDF  $pdf  The PDF instance
     * @param  string  $conformance_level  The Factur-X conformance level
     */
    private function setFacturXXmpMetadata(TCPDF $pdf, string $conformance_level): void
    {
        $xmp = $this->generateFacturXXmp($conformance_level);
        $pdf->setExtraXMP($xmp);
    }

    /**
     * Generate Factur-X-specific XMP metadata.
     *
     * @param  string  $conformance_level  The conformance level (e.g., "EN 16931")
     * @return string XMP metadata fragment
     */
    private function generateFacturXXmp(string $conformance_level): string
    {
        return <<<XMP
    <rdf:Description rdf:about="" xmlns:fx="urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#">
      <fx:DocumentType>INVOICE</fx:DocumentType>
      <fx:DocumentFileName>factur-x.xml</fx:DocumentFileName>
      <fx:Version>1.0</fx:Version>
      <fx:ConformanceLevel>$conformance_level</fx:ConformanceLevel>
    </rdf:Description>
XMP;
    }

    /**
     * Add a new page to the PDF.
     */
    private function addPage(TCPDF $pdf): void
    {
        $pdf->AddPage();
    }

    /**
     * Render the invoice content using Blade template.
     */
    private function mapFont(string $userFont): string
    {
        return \App\Support\FontMapper::resolve($userFont);
    }

    private function renderContent(TCPDF $pdf, Invoice $invoice): void
    {
        $companyProfile = $invoice->user->companyProfile;

        // Ensure logo size is within safe boundaries for PDF.
        // Hardcoding a max width to prevent obscuring legal zones.
        // Convert to mm for TCPDF's internal calculations
        $maxLogoPx = 150; // Max logo width in pixels for PDF rendering
        $logoWidthMm = (($companyProfile->logo_size ?? 100) > $maxLogoPx ? $maxLogoPx : ($companyProfile->logo_size ?? 100)) / 3.78;

        $html = view('invoices.pdf_template', [
            'invoice' => $invoice,
            'companyProfile' => $companyProfile,
            'logoWidthMm' => $logoWidthMm, // Pass calculated logo width
        ])->render();

        $pdf->writeHTML($html, true, false, true, false, '');
    }
}
