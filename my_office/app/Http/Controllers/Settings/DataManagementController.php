<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ExportAllDataJob;
use App\Jobs\RunBackupJob;
use App\Services\DataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataManagementController extends Controller
{
    /**
     * Show the data management page.
     */
    public function index(): Response
    {
        return Inertia::render('Settings/DataManagement', [
            'lastExport' => null, 
        ]);
    }

    /**
     * Export all data to a ZIP file (Async).
     */
    public function exportAll(Request $request): RedirectResponse
    {
        ExportAllDataJob::dispatch($request->user());

        return redirect()->back()->with('success', 'L\'exportation de vos données a été lancée en arrière-plan. Vous recevrez une notification dans votre tableau de bord une fois terminée.');
    }

    /**
     * Download a private ZIP export.
     */
    public function downloadExport(string $fileName, Request $request): BinaryFileResponse
    {
        // Strip any directory traversal attempt
        $fileName = basename($fileName);

        // Strict format validation: export_{userId}_{timestamp}_{random}.zip
        if (!preg_match('/^export_(\d+)_\d+_[a-zA-Z0-9]+\.zip$/', $fileName, $matches)) {
            abort(404, 'Fichier introuvable ou expiré.');
        }

        // Exact user ID match (not str_contains to avoid partial ID matching)
        if ((int) $matches[1] !== $request->user()->id) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce fichier.');
        }

        $filePath = storage_path("app/private/exports/{$fileName}");

        if (!file_exists($filePath)) {
            abort(404, 'Fichier introuvable ou expiré.');
        }

        // Defense-in-depth: verify resolved path stays within expected directory
        $realPath = realpath($filePath);
        $expectedDir = realpath(storage_path('app/private/exports'));
        if ($realPath === false || !str_starts_with($realPath, $expectedDir . DIRECTORY_SEPARATOR)) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce fichier.');
        }

        return response()->download($realPath);
    }

    public function exportClients(DataExportService $exportService, Request $request): BinaryFileResponse
    {
        return $this->downloadTempExport('clients_' . now()->format('Y-m-d') . '.csv', function ($path) use ($exportService, $request) {
            $exportService->exportClientsCsv($request->user()->id, $path);
        });
    }

    public function exportClientsJson(DataExportService $exportService, Request $request): BinaryFileResponse
    {
        return $this->downloadTempExport('clients_' . now()->format('Y-m-d') . '.json', function ($path) use ($exportService, $request) {
            $exportService->exportClientsJson($request->user()->id, $path);
        });
    }

    public function exportInvoices(DataExportService $exportService, Request $request): BinaryFileResponse
    {
        return $this->downloadTempExport('factures_' . now()->format('Y-m-d') . '.csv', function ($path) use ($exportService, $request) {
            $exportService->exportInvoicesCsv($request->user()->id, $path);
        });
    }

    public function exportInvoicesJson(DataExportService $exportService, Request $request): BinaryFileResponse
    {
        return $this->downloadTempExport('factures_' . now()->format('Y-m-d') . '.json', function ($path) use ($exportService, $request) {
            $exportService->exportInvoicesJson($request->user()->id, $path);
        });
    }

    public function exportFec(DataExportService $exportService, Request $request): BinaryFileResponse
    {
        return $this->downloadTempExport('FEC_' . now()->format('Y-m-d') . '.txt', function ($path) use ($exportService, $request) {
            $exportService->exportFec($request->user()->id, $path);
        });
    }

    private function downloadTempExport(string $fileName, callable $exporter): BinaryFileResponse
    {
        $filePath = storage_path('app/temp/' . $fileName);

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $exporter($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    /**
     * Trigger a system backup (Async).
     */
    public function runBackup(Request $request): RedirectResponse
    {
        RunBackupJob::dispatch();

        return redirect()->back()->with('success', 'La sauvegarde de la base de données a été lancée en arrière-plan.');
    }
}