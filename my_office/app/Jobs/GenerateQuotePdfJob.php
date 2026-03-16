<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Quote;
use App\Notifications\PdfGeneratedNotification;
use App\Services\QuotePdfService;
use App\Services\TemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateQuotePdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Quote $quote
    ) {
        $this->onQueue('quotes');
    }

    /**
     * Execute the job.
     */
    public function handle(QuotePdfService $pdfService, TemplateService $templateService): void
    {
        $this->quote->load(['client', 'lineItems', 'user.companyProfile']);

        // 1. Resolve Branding and capture snapshot only if needed
        $branding = $templateService->resolveBranding($this->quote);
        
        // Only update snapshot if it's missing OR if the quote is still a draft
        $updateSnapshot = is_null($this->quote->branding_snapshot) || $this->quote->status === \App\Enums\QuoteStatus::Brouillon;
        $brandingSnapshot = $updateSnapshot ? $branding->toArray() : $this->quote->branding_snapshot;

        // 2. Generate PDF using service
        $pdfContent = $pdfService->generate($this->quote, $branding);

        // 3. Save to storage
        $file_path = $this->buildFilePath();
        Storage::put($file_path, $pdfContent);

        // 4. Update snapshot in DB
        $updateData = [];
        if ($updateSnapshot) {
            $updateData['branding_snapshot'] = $brandingSnapshot;
        }

        if (!empty($updateData)) {
            $this->quote->update($updateData);
        }

        // 5. Send notification to user
        $this->quote->user->notify(new PdfGeneratedNotification(
            documentType: 'devis',
            documentNumber: $this->quote->quote_number,
            documentId: $this->quote->id,
            downloadRoute: 'quotes.download'
        ));
    }

    /**
     * Build the storage path for the PDF file.
     */
    private function buildFilePath(): string
    {
        $year = $this->quote->created_at->format('Y');
        $month = $this->quote->created_at->format('m');
        $filename = 'devis-'.$this->quote->quote_number.'.pdf';

        return "quotes/{$year}/{$month}/{$filename}";
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
