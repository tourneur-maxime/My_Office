<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LogoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_logo_and_it_is_processed_correctly()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create(['user_id' => $user->id]);

        // Create a fake image file
        $file = UploadedFile::fake()->image('logo.png', 800, 600);

        $response = $this->actingAs($user)
            ->post(route('logo.store'), [
                'logo' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Refresh company profile
        $companyProfile->refresh();

        // Assert logo_path is saved
        $this->assertNotNull($companyProfile->logo_path);
        $this->assertStringContainsString('logos/', $companyProfile->logo_path);
        $this->assertStringEndsWith('.webp', $companyProfile->logo_path);

        // Assert file exists in storage
        Storage::disk('public')->assertExists($companyProfile->logo_path);

        // Verify image processing: should be WebP format and resized
        $storedImage = Storage::disk('public')->get($companyProfile->logo_path);
        $this->assertNotEmpty($storedImage);

        // Check file is WebP (magic bytes: RIFF...WEBP)
        $this->assertStringContainsString('WEBP', substr($storedImage, 0, 30));
    }

    public function test_svg_logo_is_stored_without_conversion()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create(['user_id' => $user->id]);

        // Create a fake SVG file
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg"><circle r="50"/></svg>';
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $response = $this->actingAs($user)
            ->post(route('logo.store'), [
                'logo' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $companyProfile->refresh();

        $this->assertNotNull($companyProfile->logo_path);
        $this->assertStringEndsWith('.svg', $companyProfile->logo_path);

        Storage::disk('public')->assertExists($companyProfile->logo_path);
    }

    public function test_uploading_new_logo_deletes_old_logo()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'logo_path' => 'logos/old-logo.webp',
        ]);

        // Create old logo file
        Storage::disk('public')->put('logos/old-logo.webp', 'fake-old-logo-content');

        // Upload new logo
        $newFile = UploadedFile::fake()->image('new-logo.png');

        $response = $this->actingAs($user)
            ->post(route('logo.store'), [
                'logo' => $newFile,
            ]);

        $response->assertRedirect();

        $companyProfile->refresh();

        // Old logo should be deleted
        Storage::disk('public')->assertMissing('logos/old-logo.webp');

        // New logo should exist
        $this->assertNotNull($companyProfile->logo_path);
        $this->assertNotEquals('logos/old-logo.webp', $companyProfile->logo_path);
        Storage::disk('public')->assertExists($companyProfile->logo_path);
    }

    public function test_user_can_delete_logo()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $logoPath = 'logos/test-logo.webp';
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'logo_path' => $logoPath,
        ]);

        // Create logo file
        Storage::disk('public')->put($logoPath, 'fake-logo-content');

        $response = $this->actingAs($user)
            ->delete(route('logo.destroy'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $companyProfile->refresh();

        // Logo path should be null
        $this->assertNull($companyProfile->logo_path);

        // File should be deleted from storage
        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_invalid_file_types_are_rejected()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        // Try to upload a text file
        $file = UploadedFile::fake()->create('document.txt', 100);

        $response = $this->actingAs($user)
            ->post(route('logo.store'), [
                'logo' => $file,
            ]);

        $response->assertSessionHasErrors('logo');
    }

    public function test_logo_upload_requires_authentication()
    {
        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->post(route('logo.store'), [
            'logo' => $file,
        ]);

        $response->assertRedirect(route('login'));
    }

}
