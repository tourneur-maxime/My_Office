<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;

class DataExportService
{
    /**
     * Export clients to a CSV file.
     */
    public function exportClientsCsv(int $userId, string $filePath): string
    {
        $writer = SimpleExcelWriter::create($filePath);

        Prospect::where('user_id', $userId)
            ->cursor()
            ->each(function (Prospect $prospect) use ($writer) {
                $writer->addRow([
                    'Nom' => $prospect->name,
                    'Entreprise' => $prospect->company,
                    'Email' => $prospect->email,
                    'Téléphone' => $prospect->phone,
                    'Adresse' => $prospect->address,
                    'Code Postal' => $prospect->zip_code,
                    'Ville' => $prospect->city,
                    'SIRET' => $prospect->siret,
                    'Type' => $prospect->status, // 'prospect' or 'client'
                    'Date Création' => $prospect->created_at->format('d/m/Y H:i'),
                ]);
            });

        return $filePath;
    }

    /**
     * Export quotes to a CSV file.
     */
    public function exportQuotesCsv(int $userId, string $filePath): string
    {
        $writer = SimpleExcelWriter::create($filePath);

        Quote::where('user_id', $userId)
            ->with('client')
            ->cursor()
            ->each(function (Quote $quote) use ($writer) {
                $writer->addRow([
                    'Numéro' => $quote->quote_number,
                    'Client' => $quote->client?->name . ($quote->client?->company ? ' (' . $quote->client->company . ')' : ''),
                    'Statut' => $quote->status instanceof \BackedEnum ? $quote->status->value : $quote->status,
                    'Date' => $quote->created_at->format('d/m/Y'),
                    'Expire le' => $quote->expires_at ? $quote->expires_at->format('d/m/Y') : '',
                    'Sous-total HT' => number_format($quote->subtotal, 2, ',', ' '),
                    'TVA' => number_format($quote->vat_amount, 2, ',', ' '),
                    'Total TTC' => number_format($quote->total, 2, ',', ' '),
                ]);
            });

        return $filePath;
    }

    /**
     * Export invoices to a CSV file.
     */
    public function exportInvoicesCsv(int $userId, string $filePath): string
    {
        $writer = SimpleExcelWriter::create($filePath);

        Invoice::where('user_id', $userId)
            ->with('client')
            ->cursor()
            ->each(function (Invoice $invoice) use ($writer) {
                $writer->addRow([
                    'Numéro' => $invoice->invoice_number,
                    'Client' => $invoice->client?->name . ($invoice->client?->company ? ' (' . $invoice->client->company . ')' : ''),
                    'Statut' => $invoice->status,
                    'Date émission' => $invoice->issue_date ? $invoice->issue_date->format('d/m/Y') : '',
                    'Date échéance' => $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '',
                    'Sous-total HT' => number_format($invoice->subtotal, 2, ',', ' '),
                    'TVA' => number_format($invoice->vat_amount, 2, ',', ' '),
                    'Total TTC' => number_format($invoice->total, 2, ',', ' '),
                    'Conformité Factur-X' => $invoice->is_compliant ? 'Oui' : 'Non',
                ]);
            });

        return $filePath;
    }

    /**
     * Export all data to a structured JSON file.
     */
    public function exportAllToJson(int $userId, string $filePath): string
    {
        $handle = fopen($filePath, 'w');
        fwrite($handle, "{\n");
        fwrite($handle, '  "exported_at": "' . now()->toIso8601String() . '",' . "\n");

        // Clients
        fwrite($handle, '  "clients": [' . "\n");
        $first = true;
        Prospect::where('user_id', $userId)->cursor()->each(function ($prospect) use ($handle, &$first) {
            if (!$first) fwrite($handle, ",\n");
            fwrite($handle, '    ' . json_encode($prospect->toArray(), JSON_UNESCAPED_UNICODE));
            $first = false;
        });
        fwrite($handle, "\n  ],\n");

        // Quotes
        fwrite($handle, '  "quotes": [' . "\n");
        $first = true;
        Quote::where('user_id', $userId)->with('lineItems')->cursor()->each(function ($quote) use ($handle, &$first) {
            if (!$first) fwrite($handle, ",\n");
            fwrite($handle, '    ' . json_encode($quote->toArray(), JSON_UNESCAPED_UNICODE));
            $first = false;
        });
        fwrite($handle, "\n  ],\n");

        // Invoices
        fwrite($handle, '  "invoices": [' . "\n");
        $first = true;
        Invoice::where('user_id', $userId)->with('lineItems')->cursor()->each(function ($invoice) use ($handle, &$first) {
            if (!$first) fwrite($handle, ",\n");
            fwrite($handle, '    ' . json_encode($invoice->toArray(), JSON_UNESCAPED_UNICODE));
            $first = false;
        });
        fwrite($handle, "\n  ]\n");
        fwrite($handle, "}\n");

        fclose($handle);

        return $filePath;
    }

    /**
     * Export clients to a JSON file.
     */
    public function exportClientsJson(int $userId, string $filePath): string
    {
        $data = Prospect::where('user_id', $userId)->get();
        file_put_contents($filePath, $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $filePath;
    }

    /**
     * Export invoices to a JSON file.
     */
    public function exportInvoicesJson(int $userId, string $filePath): string
    {
        $data = Invoice::where('user_id', $userId)->with('lineItems')->get();
        file_put_contents($filePath, $data->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $filePath;
    }

    /**
     * Export FEC (Fichier des Ecritures Comptables) per Art. A47 A-1 LPF.
     * Tab-separated file with mandatory columns.
     */
    public function exportFec(int $userId, string $filePath): string
    {
        $handle = fopen($filePath, 'w');

        // FEC header (tab-separated, mandatory columns per LPF)
        $headers = [
            'JournalCode',
            'JournalLib',
            'EcritureNum',
            'EcritureDate',
            'CompteNum',
            'CompteLib',
            'CompAuxNum',
            'CompAuxLib',
            'PieceRef',
            'PieceDate',
            'EcritureLib',
            'Debit',
            'Credit',
            'EcritureLet',
            'DateLet',
            'ValidDate',
            'Montantdevise',
            'Idevise',
        ];
        fputcsv($handle, $headers, "\t");

        $entryNum = 1;
        Invoice::where('user_id', $userId)
            ->with('client')
            ->where('status', '!=', InvoiceStatus::Brouillon)
            ->orderBy('issue_date')
            ->cursor()
            ->each(function (Invoice $invoice) use ($handle, &$entryNum) {
                $date = $invoice->issue_date ? $invoice->issue_date->format('Ymd') : $invoice->created_at->format('Ymd');
                $clientCode = 'CLI'.str_pad((string) $invoice->client_id, 5, '0', STR_PAD_LEFT);
                $clientName = $invoice->client?->name ?? 'Client inconnu';
                $ref = $invoice->invoice_number;
                $isCreditNote = $invoice->type === InvoiceType::Avoir;

                // Revenue entry (product account 706)
                fputcsv($handle, [
                    'VE',
                    'Journal des Ventes',
                    str_pad((string) $entryNum, 6, '0', STR_PAD_LEFT),
                    $date,
                    '706000',
                    'Prestations de services',
                    $clientCode,
                    $clientName,
                    $ref,
                    $date,
                    ($isCreditNote ? 'Avoir ' : 'Facture ').$ref,
                    $isCreditNote ? number_format(abs($invoice->subtotal), 2, ',', '') : '0,00',
                    $isCreditNote ? '0,00' : number_format($invoice->subtotal, 2, ',', ''),
                    '',
                    '',
                    $date,
                    '',
                    'EUR',
                ], "\t");

                // VAT entry (account 44571)
                if ($invoice->vat_amount != 0) {
                    fputcsv($handle, [
                        'VE',
                        'Journal des Ventes',
                        str_pad((string) $entryNum, 6, '0', STR_PAD_LEFT),
                        $date,
                        '445710',
                        'TVA collectée',
                        '',
                        '',
                        $ref,
                        $date,
                        'TVA sur '.($isCreditNote ? 'avoir ' : 'facture ').$ref,
                        $isCreditNote ? number_format(abs($invoice->vat_amount), 2, ',', '') : '0,00',
                        $isCreditNote ? '0,00' : number_format($invoice->vat_amount, 2, ',', ''),
                        '',
                        '',
                        $date,
                        '',
                        'EUR',
                    ], "\t");
                }

                // Client receivable entry (account 411)
                fputcsv($handle, [
                    'VE',
                    'Journal des Ventes',
                    str_pad((string) $entryNum, 6, '0', STR_PAD_LEFT),
                    $date,
                    '411000',
                    'Clients',
                    $clientCode,
                    $clientName,
                    $ref,
                    $date,
                    ($isCreditNote ? 'Avoir ' : 'Facture ').$ref.' - '.$clientName,
                    $isCreditNote ? '0,00' : number_format($invoice->total, 2, ',', ''),
                    $isCreditNote ? number_format(abs($invoice->total), 2, ',', '') : '0,00',
                    '',
                    '',
                    $date,
                    '',
                    'EUR',
                ], "\t");

                $entryNum++;
            });

        fclose($handle);

        return $filePath;
    }
}