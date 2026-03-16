<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Imports\ClientCsvImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportController extends Controller
{
    public function __construct(
        private ClientCsvImporter $importer
    ) {}

    /**
     * Import clients from CSV file.
     */
    public function clients(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [
            'csv_file.required' => 'Veuillez selectionner un fichier CSV.',
            'csv_file.mimes' => 'Le fichier doit etre au format CSV.',
            'csv_file.max' => 'Le fichier ne doit pas depasser 5 Mo.',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());

        $result = $this->importer->import($content, $request->user());

        $message = "Import termine: {$result['success_count']} clients importes avec succes";
        if ($result['fail_count'] > 0) {
            $message .= ", {$result['fail_count']} erreurs";
        }

        if (! empty($result['errors'])) {
            session()->flash('import_errors', $result['errors']);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Download the client import template.
     */
    public function clientsTemplate(): BinaryFileResponse
    {
        $templatePath = public_path('templates/client_import_template.csv');

        return response()->download($templatePath, 'modele-import-clients.csv');
    }
}
