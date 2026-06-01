<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DataExportService;
use App\Notifications\Settings\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExportAllDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(DataExportService $exportService): void
    {
        $userId = $this->user->id;
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(16);
        $tempDir = storage_path("app/temp/export_{$userId}_{$timestamp}");
        
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            // 1. Generate CSVs and JSON
            $exportService->exportClientsCsv($userId, "{$tempDir}/clients.csv");
            $exportService->exportQuotesCsv($userId, "{$tempDir}/quotes.csv");
            $exportService->exportInvoicesCsv($userId, "{$tempDir}/invoices.csv");
            $exportService->exportAllToJson($userId, "{$tempDir}/data_full.json");

            // 2. Collect PDFs
            $invoicePdfDir = "{$tempDir}/invoices";
            mkdir($invoicePdfDir, 0755, true);

            $this->user->invoices()->whereNotNull('pdf_path')->each(function ($invoice) use ($invoicePdfDir) {
                if (Storage::disk('local')->exists($invoice->pdf_path)) {
                    $fileName = basename($invoice->pdf_path);
                    copy(Storage::disk('local')->path($invoice->pdf_path), "{$invoicePdfDir}/{$fileName}");
                }
            });

            // 3. Create ZIP in PRIVATE storage
            $zipName = "export_{$userId}_{$timestamp}_{$random}.zip";
            $zipPath = storage_path("app/private/exports/{$zipName}");
            
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($tempDir),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($tempDir) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
            }

            // 4. Notify user
            $this->user->notify(new ExportReadyNotification($zipName));

            // 5. Cleanup temp files
            $this->cleanup($tempDir);

            // 6. Cleanup old exports (> 24h)
            $this->cleanupOldExports();

        } catch (\Exception $e) {
            Log::error("Export failed for user {$userId}: " . $e->getMessage());
            $this->cleanup($tempDir);
            throw $e;
        }
    }

    protected function cleanupOldExports(): void
    {
        $exportDir = storage_path("app/private/exports");
        if (!file_exists($exportDir)) return;

        foreach (glob("{$exportDir}/*.zip") as $file) {
            if (time() - filemtime($file) > 86400) { // 24 hours
                unlink($file);
            }
        }
    }

    protected function cleanup($dir): void
    {
        if (!file_exists($dir)) return;
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($dir);
    }
}