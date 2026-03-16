<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ClientCsvImporter
{
    private array $errors = [];

    private int $successCount = 0;

    private int $failCount = 0;

    /**
     * Import clients from CSV content.
     */
    public function import(string $csvContent, User $user): array
    {
        $this->errors = [];
        $this->successCount = 0;
        $this->failCount = 0;

        $lines = explode("\n", $csvContent);
        $headers = [];

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line, ';');

            // First line is headers
            if ($lineNumber === 0) {
                $headers = array_map('trim', $row);

                continue;
            }

            if (count($row) !== count($headers)) {
                $this->errors[] = [
                    'line' => $lineNumber + 1,
                    'message' => 'Nombre de colonnes incorrect.',
                ];
                $this->failCount++;

                continue;
            }

            $data = array_combine($headers, $row);
            $this->processRow($data, $lineNumber + 1, $user);
        }

        return [
            'success_count' => $this->successCount,
            'fail_count' => $this->failCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Process a single row of data.
     */
    private function processRow(array $data, int $lineNumber, User $user): void
    {
        // Normalize column names
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[strtolower(trim($key))] = trim($value);
        }

        // Map common column variations
        $name = $normalized['nom'] ?? $normalized['name'] ?? null;
        $company = $normalized['entreprise'] ?? $normalized['company'] ?? null;
        $email = $normalized['email'] ?? null;
        $phone = $normalized['telephone'] ?? $normalized['phone'] ?? null;
        $address = $normalized['adresse'] ?? $normalized['address'] ?? null;
        $zipCode = $normalized['code_postal'] ?? $normalized['zip_code'] ?? null;
        $city = $normalized['ville'] ?? $normalized['city'] ?? null;
        $siret = $normalized['siret'] ?? null;

        // Validate
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.email' => 'L\'email n\'est pas valide.',
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'line' => $lineNumber,
                'message' => implode(' ', $validator->errors()->all()),
            ];
            $this->failCount++;

            return;
        }

        try {
            Prospect::create([
                'user_id' => $user->id,
                'name' => $name,
                'company' => $company,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'zip_code' => $zipCode,
                'city' => $city,
                'siret' => $siret,
                'is_client' => false,
            ]);
            $this->successCount++;
        } catch (\Exception $e) {
            $this->errors[] = [
                'line' => $lineNumber,
                'message' => 'Erreur lors de la creation: '.$e->getMessage(),
            ];
            $this->failCount++;
        }
    }
}
