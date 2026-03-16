<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\ImportClientsJob;
use App\Services\ClientImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\SimpleExcel\SimpleExcelReader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    /**
     * Show the client import page.
     */
    public function showImportClients(): Response
    {
        return Inertia::render('Settings/ImportClients');
    }

    /**
     * Handle the client import upload.
     */
    public function importClients(Request $request, ClientImportService $importService): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'], // 10MB limit
        ]);

        $file = $request->file('file');
        $filePath = $file->store('temp_imports');
        
        // Ensure the file has a .csv extension so Spatie can detect the type
        $newFilePath = $filePath . '.csv';
        Storage::disk('local')->move($filePath, $newFilePath);
        $fullPath = Storage::disk('local')->path($newFilePath);

        try {
            // Check row count for background processing threshold (AC 3: > 50 rows)
            $rowCount = SimpleExcelReader::create($fullPath)->getRows()->count();

            if ($rowCount > 50) {
                ImportClientsJob::dispatch($request->user(), $fullPath);
                return redirect()->route('settings.data.index')->with('success', 'L\'importation de vos clients (> 50 lignes) a été lancée en arrière-plan. Vous recevrez une notification une fois terminée.');
            }

            // Small files: process immediately
            $result = $importService->import($request->user()->id, $fullPath);
            
            // Cleanup
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            if ($result['failed_count'] > 0) {
                \Illuminate\Support\Facades\Log::warning('Import failed', $result['errors']);
                return redirect()->route('settings.data.index')->with('warning', "Importation terminée avec {$result['success_count']} succès et {$result['failed_count']} erreurs.");
            }

            return redirect()->route('settings.data.index')->with('success', "{$result['success_count']} clients ont été importés avec succès.");

        } catch (\Exception $e) {
            // Cleanup on failure
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return redirect()->back()->withErrors(['file' => 'Erreur lors du traitement du fichier : ' . $e->getMessage()]);
        }
    }

    /**
     * Download the client import CSV template.
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $headers = ['name', 'email', 'company', 'phone', 'address', 'zip_code', 'city', 'siret'];
        $filePath = storage_path('app/temp/client_import_template.csv');

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $handle = fopen($filePath, 'w');
        fputcsv($handle, $headers);
        fclose($handle);

        return response()->download($filePath, 'template_import_clients.csv')->deleteFileAfterSend(true);
    }
}