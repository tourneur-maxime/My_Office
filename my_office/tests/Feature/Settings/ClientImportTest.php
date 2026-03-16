<?php

namespace Tests\Feature\Settings;

use App\Models\Prospect;
use App\Models\User;
use App\Services\ClientImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_valid_clients_from_csv(): void
    {
        $user = User::factory()->create();
        
        $csvContent = "name,email,company,phone,address,zip_code,city,siret\n";
        $csvContent .= "John Doe,john@example.com,ACME,0102030405,123 Street,75001,Paris,12345678901234\n";
        $csvContent .= "Jane Smith,jane@example.com,Global Corp,0607080910,456 Ave,69001,Lyon,98765432109876";

        $filePath = storage_path('app/temp_import.csv');
        file_put_contents($filePath, $csvContent);

        $service = new ClientImportService();
        $result = $service->import($user->id, $filePath);

        $this->assertEquals(2, $result['success_count']);
        $this->assertEquals(0, $result['failed_count']);
        
        // Verify Eloquent decrypts correctly
        $prospect = Prospect::where('name', 'John Doe')->first();
        $this->assertEquals('john@example.com', $prospect->email);
        $this->assertEquals('12345678901234', $prospect->siret);

        // Verify RAW database encryption (REAL check)
        $raw = \DB::table('prospects')->where('name', 'John Doe')->first();
        $this->assertNotEquals('john@example.com', $raw->email);
        $this->assertNotEquals('12345678901234', $raw->siret);
        $this->assertStringStartsWith('eyJpdiI6', $raw->email); // Laravel encryption prefix

        unlink($filePath);
    }

    public function test_it_prevents_duplicate_import_in_same_session(): void
    {
        $user = User::factory()->create();
        
        $csvContent = "name,email,company\n";
        $csvContent .= "John Doe,john@example.com,ACME\n";
        $csvContent .= "John Duplicate,john@example.com,ACME Duplicate";

        $filePath = storage_path('app/temp_import_dup.csv');
        file_put_contents($filePath, $csvContent);

        $service = new ClientImportService();
        $result = $service->import($user->id, $filePath);

        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(1, $result['failed_count']);
        $this->assertStringContainsString('existe déjà', $result['errors'][0]['messages'][0]);

        unlink($filePath);
    }

    public function test_it_reports_validation_errors_for_invalid_rows(): void
    {
        $user = User::factory()->create();
        
        $csvContent = "name,email,company,phone,address,zip_code,city,siret\n";
        $csvContent .= "Invalid SIRET,invalid-siret@example.com,,,Address,75001,Paris,ABC12345678901\n"; // Non-numeric SIRET
        $csvContent .= ",missing-name@example.com,,,Address,75001,Paris,12345678901234\n"; // Missing name
        $csvContent .= "Valid Row,valid@example.com,ACME,0102030405,123 Street,75001,Paris,12345678901234";

        $filePath = storage_path('app/temp_import_invalid.csv');
        file_put_contents($filePath, $csvContent);

        $service = new ClientImportService();
        $result = $service->import($user->id, $filePath);

        $this->assertEquals(1, $result['success_count']);
        $this->assertEquals(2, $result['failed_count']);
        $this->assertCount(2, $result['errors']);
        
        // Verify specific SIRET error message
        $this->assertStringContainsString('doit contenir 14 chiffres', $result['errors'][0]['messages'][0]);

        unlink($filePath);
    }

    public function test_controller_dispatches_job_only_for_large_files(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $user = User::factory()->create();
        $this->actingAs($user);

        // Small file (1 row) -> Processed immediately
        $csvContent = "name,email\nController Test,controller@example.com";
        $tempFile = sys_get_temp_dir() . '/test_small_' . uniqid() . '.csv';
        file_put_contents($tempFile, $csvContent);
        
        $file = new UploadedFile($tempFile, 'small.csv', 'text/csv', null, true);

        $response = $this->post(route('settings.data.import-clients'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('settings.data.index'));
        $response->assertSessionHas('success');
        \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Jobs\ImportClientsJob::class);
        $this->assertDatabaseHas('prospects', ['name' => 'Controller Test']);

        // Large file (> 50 rows) -> Dispatched to background
        $largeCsv = "name,email\n";
        for ($i = 0; $i < 51; $i++) {
            $largeCsv .= "Large User $i,large$i@example.com\n";
        }
        $tempLargeFile = sys_get_temp_dir() . '/test_large_' . uniqid() . '.csv';
        file_put_contents($tempLargeFile, $largeCsv);
        
        $largeFile = new UploadedFile($tempLargeFile, 'large.csv', 'text/csv', null, true);

        $response = $this->post(route('settings.data.import-clients'), [
            'file' => $largeFile,
        ]);

        $response->assertRedirect(route('settings.data.index'));
        $response->assertSessionHas('success', fn($val) => str_contains($val, 'arrière-plan'));
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ImportClientsJob::class);

        // Cleanup
        if (file_exists($tempFile)) unlink($tempFile);
        if (file_exists($tempLargeFile)) unlink($tempLargeFile);
        Storage::disk('local')->deleteDirectory('temp_imports');
    }
}