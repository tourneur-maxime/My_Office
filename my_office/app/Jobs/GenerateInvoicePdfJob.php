<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\FacturXValidationException;
use App\Models\Invoice;
use App\Notifications\PdfGeneratedNotification;
use App\Services\FacturX\ComplianceEngine;
use App\Services\FacturX\PdfGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Job for asynchronous Factur-X PDF generation.
 *
 * Implements NFR-P4: PDF generation must happen asynchronously in a queue.
 * Uses ComplianceEngine to generate complete Factur-X PDFs with embedded XML.
 */
class GenerateInvoicePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {
        $this->onQueue('invoices');
    }

    /**
     * Execute the job.
     *
     * Attempts to generate a complete Factur-X PDF. Falls back to simple PDF
     * if XML validation fails (e.g., missing schemas or validation errors).
     */
    public function handle(
        ComplianceEngine $compliance_engine,
        PdfGeneratorService $pdf_generator_service
    ): void {
        try {
            $pdf_content = $compliance_engine->generate($this->invoice);
        } catch (FacturXValidationException|\Throwable $e) {
            Log::warning('Factur-X generation failed, generating simple PDF', [
                'invoice_id' => $this->invoice->id,
                'error' => $e->getMessage(),
            ]);

            $pdf_content = $pdf_generator_service->generate($this->invoice);
        }

        $file_path = $this->buildFilePath();

        Storage::put($file_path, $pdf_content);

        // Send notification to user
        $this->invoice->user->notify(new PdfGeneratedNotification(
            documentType: 'facture',
            documentNumber: $this->invoice->invoice_number,
            documentId: $this->invoice->id,
            downloadRoute: 'invoices.download'
        ));
    }

    /**
     * Build the storage path for the PDF file.
     *
     * Path format: invoices/{year}/{month}/{invoice_number}.pdf
     */
    private function buildFilePath(): string
    {
        $year = $this->invoice->created_at->format('Y');
        $month = $this->invoice->created_at->format('m');
        $filename = 'facture-'.$this->invoice->invoice_number.'.pdf';

        return "invoices/{$year}/{$month}/{$filename}";
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * Exponential backoff: 1min, 5min, 15min.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
