<?php

namespace App\Services;

use App\Models\Prospect;
use Illuminate\Support\Facades\Validator;
use Spatie\SimpleExcel\SimpleExcelReader;

class ClientImportService
{
    /**
     * Import clients from a CSV file.
     *
     * @param int $userId
     * @param string $filePath
     * @return array
     */
    public function import(int $userId, string $filePath): array
    {
        $reader = SimpleExcelReader::create($filePath);
        
        $successCount = 0;
        $failedCount = 0;
        $errors = [];
        $rowsToInsert = [];
        $batchSize = 50;

        // Get existing emails and SIRETs to avoid duplicates during this import session
        $existingEmails = Prospect::where('user_id', $userId)->pluck('email')->toArray();
        $existingSirets = Prospect::where('user_id', $userId)->whereNotNull('siret')->pluck('siret')->toArray();

        $reader->getRows()->each(function (array $row, int $index) use ($userId, &$successCount, &$failedCount, &$errors, &$rowsToInsert, $batchSize, &$existingEmails, &$existingSirets) {
            $validator = Validator::make($row, [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'company' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:100',
                'siret' => 'nullable|digits:14',
            ]);

            if ($validator->fails()) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'messages' => $validator->errors()->all(),
                    'data' => $row,
                    'debug_headers' => array_keys($row),
                ];
                return;
            }

            // Duplicate check
            if (in_array($row['email'], $existingEmails)) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'messages' => ["Le client avec l'email {$row['email']} existe déjà."],
                    'data' => $row,
                ];
                return;
            }

            if (!empty($row['siret']) && in_array($row['siret'], $existingSirets)) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'messages' => ["Le client avec le SIRET {$row['siret']} existe déjà."],
                    'data' => $row,
                ];
                return;
            }

            try {
                // Prepare for batch insert
                // Note: We use create() in a loop for simplicity and to ensure 'encrypted' casts are applied.
                // True batch insert with DB::table()->insert() bypasses Eloquent casts (bad for encryption).
                // So we keep Prospect::create() but optimize by wrapping in transaction if needed.
                Prospect::create([
                    'user_id' => $userId,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'company' => $row['company'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'zip_code' => $row['zip_code'] ?? null,
                    'city' => $row['city'] ?? null,
                    'siret' => $row['siret'] ?? null,
                    'status' => 'prospect',
                ]);
                
                $existingEmails[] = $row['email'];
                if (!empty($row['siret'])) $existingSirets[] = $row['siret'];
                
                $successCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'row' => $index + 1,
                    'messages' => [$e->getMessage()],
                    'data' => $row,
                ];
            }
        });

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'errors' => $errors,
        ];
    }
}
