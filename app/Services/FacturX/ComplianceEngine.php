<?php

declare(strict_types=1);

namespace App\Services\FacturX;

use App\Exceptions\FacturXValidationException;
use App\Models\Invoice;

/**
 * Orchestrates the complete Factur-X generation process.
 *
 * This engine coordinates XML generation, validation, PDF generation,
 * XML embedding, and XMP metadata to produce a compliant Factur-X invoice.
 */
class ComplianceEngine
{
    private const CONFORMANCE_LEVEL = 'EN 16931';

    private ?ValidationReport $last_report = null;

    public function __construct(
        private FacturXService $facturx_service,
        private XsdValidatorService $xsd_validator,
        private SchematronValidatorService $schematron_validator,
        private PdfGeneratorService $pdf_generator,
    ) {}

    /**
     * Generate a complete Factur-X PDF with embedded XML and XMP metadata.
     *
     * @param  Invoice  $invoice  The invoice to generate Factur-X for
     * @return string Raw PDF content with embedded XML
     *
     * @throws FacturXValidationException If XML validation fails
     */
    public function generate(Invoice $invoice): string
    {
        $xml_content = $this->generateAndValidateXml($invoice);

        $pdf_content = $this->pdf_generator->generateWithFacturX(
            $invoice,
            $xml_content,
            self::CONFORMANCE_LEVEL
        );

        $this->calculateAndStoreHash($invoice, $pdf_content);

        return $pdf_content;
    }

    /**
     * Generate Factur-X PDF with detailed validation report.
     *
     * @param  Invoice  $invoice  The invoice to generate Factur-X for
     * @return array{pdf: string|null, report: ValidationReport}
     */
    public function generateWithReport(Invoice $invoice): array
    {
        $this->last_report = new ValidationReport;

        try {
            $xml_content = $this->generateAndValidateXmlWithReport($invoice);

            $this->last_report->addSuccess(
                'PDF_GENERATION_SUCCESS',
                'Le PDF a été généré avec succès.',
                'pdf_generation'
            );

            $pdf_content = $this->pdf_generator->generateWithFacturX(
                $invoice,
                $xml_content,
                self::CONFORMANCE_LEVEL
            );

            $this->last_report->addSuccess(
                'XML_EMBEDDING_SUCCESS',
                'Le fichier XML Factur-X a été embarqué dans le PDF.',
                'xml_embedding'
            );

            $this->last_report->addSuccess(
                'XMP_METADATA_SUCCESS',
                'Les métadonnées XMP ont été ajoutées au PDF.',
                'xmp_metadata'
            );

            $this->calculateAndStoreHash($invoice, $pdf_content);

            $this->last_report->addSuccess(
                'SIGNATURE_PREPARATION_SUCCESS',
                'Le hash du document a été calculé. Le document est prêt pour la signature numérique.',
                'signature_preparation'
            );

            return [
                'pdf' => $pdf_content,
                'report' => $this->last_report,
            ];
        } catch (FacturXValidationException $e) {
            foreach ($e->getErrors() as $error) {
                $this->last_report->addError(
                    'VALIDATION_ERROR',
                    $error,
                    'validation'
                );
            }

            return [
                'pdf' => null,
                'report' => $this->last_report,
            ];
        } catch (\Throwable $e) {
            $this->last_report->addError(
                'GENERATION_ERROR',
                'Une erreur est survenue lors de la génération : '.$e->getMessage(),
                'generation'
            );

            return [
                'pdf' => null,
                'report' => $this->last_report,
            ];
        }
    }

    /**
     * Calculate SHA-256 hash of the PDF content and store it in the invoice.
     */
    private function calculateAndStoreHash(Invoice $invoice, string $pdf_content): void
    {
        $hash = hash('sha256', $pdf_content);

        $invoice->update([
            'signature_hash' => $hash,
            'is_ready_for_signature' => true,
        ]);
    }

    /**
     * Generate and validate Factur-X XML content.
     *
     * @return string Validated XML content
     *
     * @throws FacturXValidationException
     */
    private function generateAndValidateXml(Invoice $invoice): string
    {
        $xml_content = $this->facturx_service->generateXml($invoice);

        $this->xsd_validator->validate($xml_content);

        $this->schematron_validator->validate($xml_content);

        return $xml_content;
    }

    /**
     * Generate and validate XML with detailed reporting.
     *
     * @return string Validated XML content
     *
     * @throws FacturXValidationException
     */
    private function generateAndValidateXmlWithReport(Invoice $invoice): string
    {
        $xml_content = $this->facturx_service->generateXml($invoice);

        $this->last_report->addSuccess(
            'XML_GENERATION_SUCCESS',
            'La structure XML Factur-X a été générée avec succès.',
            'xml_generation'
        );

        $this->xsd_validator->validate($xml_content);

        $this->last_report->addSuccess(
            'XSD_VALIDATION_SUCCESS',
            'La validation XSD a réussi. Le document est conforme au schéma.',
            'xsd_validation'
        );

        $this->schematron_validator->validate($xml_content);

        $this->last_report->addSuccess(
            'SCHEMATRON_VALIDATION_SUCCESS',
            'La validation Schematron a réussi. Les règles métier sont respectées.',
            'schematron_validation'
        );

        return $xml_content;
    }

    /**
     * Get the last validation report.
     */
    public function getLastReport(): ?ValidationReport
    {
        return $this->last_report;
    }

    /**
     * Get the conformance level used by this engine.
     */
    public function getConformanceLevel(): string
    {
        return self::CONFORMANCE_LEVEL;
    }
}
