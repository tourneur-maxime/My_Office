<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandingSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function validTemplateData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Mon Template',
            'primary_color' => '#AABBCC',
            'secondary_color' => '#DDEEFF',
            'font_family' => 'sans-serif',
            'logo_size' => 100,
            'logo_position' => 'left',
        ], $overrides);
    }

    public function test_authenticated_user_can_save_branding_settings()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $logoFile = UploadedFile::fake()->image('logo.png', 100, 100);
        $logoPath = $logoFile->store('logos', 'public');

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData([
                'logo_path' => $logoPath,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('templates', [
            'user_id' => $user->id,
            'name' => 'Mon Template',
            'primary_color' => '#AABBCC',
            'secondary_color' => '#DDEEFF',
        ]);
    }

    public function test_validation_rejects_invalid_primary_color()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData([
                'primary_color' => 'invalid-hex',
            ]));

        $response->assertSessionHasErrors('primary_color');
    }

    public function test_validation_rejects_invalid_secondary_color()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData([
                'secondary_color' => 'invalid-hex-again',
            ]));

        $response->assertSessionHasErrors('secondary_color');
    }

    public function test_validation_rejects_too_long_font_family()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData([
                'font_family' => str_repeat('a', 101),
            ]));

        $response->assertSessionHasErrors('font_family');
    }

    public function test_unauthenticated_user_cannot_save_branding_settings()
    {
        $response = $this->post(route('settings.branding.saveTemplate'), $this->validTemplateData());

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_company_profile_can_still_save_template()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData());

        // TemplateController creates template on user, no company profile needed
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_nullable_fields_can_be_saved()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('settings.branding.saveTemplate'), $this->validTemplateData([
                'logo_path' => null,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('templates', [
            'user_id' => $user->id,
            'name' => 'Mon Template',
        ]);
    }
}
