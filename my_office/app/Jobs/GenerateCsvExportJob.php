<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Exports\CsvExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateCsvExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public User $user,
        public string $exportType
    ) {
        $this->onQueue('exports');
    }

    public function handle(CsvExportService $csvExportService): void
    {
        $csv = match ($this->exportType) {
            'clients' => $csvExportService->exportClients($this->user->id),
            'quotes' => $csvExportService->exportQuotes($this->user->id),
            'invoices' => $csvExportService->exportInvoices($this->user->id),
            default => throw new \InvalidArgumentException("Unknown export type: {$this->exportType}"),
        };

        $filename = $this->exportType.'-'.now()->format('Y-m-d-His').'-'.Str::random(8).'.csv';
        $path = "exports/{$this->exportType}/{$filename}";

        Storage::put($path, $csv);
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
