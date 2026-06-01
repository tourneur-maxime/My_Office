<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\Settings\ImportCompleteNotification;
use App\Services\ClientImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $filePath)
    {
        $this->user = $user;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(ClientImportService $importService): void
    {
        try {
            Log::info("Starting background client import for user {$this->user->id}");
            
            $result = $importService->import($this->user->id, $this->filePath);
            
            $this->user->notify(new ImportCompleteNotification($result));
            
            Log::info("Background client import complete for user {$this->user->id}");
            
            // Cleanup temp file
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        } catch (\Exception $e) {
            Log::error("Background client import failed: " . $e->getMessage());
            throw $e;
        }
    }
}