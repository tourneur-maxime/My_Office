<?php

namespace Tests\Feature\Settings;

use App\Models\Prospect;
use App\Models\User;
use App\Services\DataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_clients_to_csv(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Prospect::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->get(route('settings.data.export-clients'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename=clients_' . now()->format('Y-m-d') . '.csv');
    }

    public function test_export_decrypted_data(): void
    {
        $user = User::factory()->create();
        
        $prospect = Prospect::create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'company' => 'ACME Inc',
            'email' => 'john@acme.com',
            'phone' => '0102030405',
            'address' => '123 encrypted st',
            'siret' => '12345678901234',
            'status' => 'client',
        ]);

        $service = new DataExportService();
        $filePath = storage_path('app/temp/test_export.csv');
        
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $service->exportClientsCsv($user->id, $filePath);

        $content = file_get_contents($filePath);
        
        // Assert content is decrypted
        $this->assertStringContainsString('john@acme.com', $content);
        $this->assertStringContainsString('12345678901234', $content);
        $this->assertStringContainsString('123 encrypted st', $content);

        unlink($filePath);
    }

    public function test_cannot_download_other_users_export(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $fileName = "export_{$otherUser->id}_2026-01-21_12-00-00_abc123.zip";
        $filePath = storage_path("app/private/exports/{$fileName}");

        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        file_put_contents($filePath, 'fake content');

        $response = $this->get(route('settings.data.download-export', ['fileName' => $fileName]));

        $response->assertStatus(403);
        unlink($filePath);
    }

    public function test_can_export_clients_to_json(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Prospect::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->get(route('settings.data.export-clients-json'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        
        $content = $response->getFile()->getContent();
        $data = json_decode($content, true);
        $this->assertCount(2, $data);
    }
}