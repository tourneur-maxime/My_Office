<?php

namespace Tests\Feature;

use App\Models\QuoteNumberSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_accessible_by_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.quotes'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Quotes')
            ->has('settings')
            ->has('preview')
        );
    }

    public function test_settings_page_defaults_are_created_if_not_exists()
    {
        $user = User::factory()->create();

        $this->assertDatabaseMissing('quote_number_settings', ['user_id' => $user->id]);

        $this->actingAs($user)->get(route('settings.quotes'));

        $this->assertDatabaseHas('quote_number_settings', [
            'user_id' => $user->id,
            'prefix' => 'DEV',
            'digit_count' => 4,
            'include_year' => true,
        ]);
    }

    public function test_settings_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => 'FAC',
            'suffix' => 'XX',
            'digit_count' => 5,
            'include_year' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quote_number_settings', [
            'user_id' => $user->id,
            'prefix' => 'FAC',
            'suffix' => 'XX',
            'digit_count' => 5,
            'include_year' => false,
        ]);
    }

    public function test_settings_update_validation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => str_repeat('a', 11), // Too long
            'digit_count' => 11, // Too big
            'include_year' => 'not-a-boolean',
        ]);

        $response->assertSessionHasErrors(['prefix', 'digit_count', 'include_year']);
    }

    public function test_settings_rejects_invalid_characters_in_prefix_and_suffix()
    {
        $user = User::factory()->create();

        // Test with special characters that could break filenames
        $response = $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => 'DEV/INVALID',
            'digit_count' => 4,
            'include_year' => true,
        ]);

        $response->assertSessionHasErrors(['prefix']);

        // Test suffix with invalid characters
        $response = $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => 'DEV',
            'suffix' => 'TEST<>',
            'digit_count' => 4,
            'include_year' => true,
        ]);

        $response->assertSessionHasErrors(['suffix']);
    }

    public function test_settings_accepts_valid_alphanumeric_with_dashes_and_underscores()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => 'DEV-2026',
            'suffix' => 'quote_v1',
            'digit_count' => 4,
            'include_year' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('quote_number_settings', [
            'user_id' => $user->id,
            'prefix' => 'DEV-2026',
            'suffix' => 'quote_v1',
        ]);
    }

    public function test_last_number_cannot_be_updated_by_form_submission()
    {
        $user = User::factory()->create();
        QuoteNumberSetting::create([
            'user_id' => $user->id,
            'prefix' => 'DEV',
            'digit_count' => 4,
            'include_year' => true,
            'last_number' => 5,
        ]);

        // Try to update last_number
        $this->actingAs($user)->patch(route('settings.quotes.update'), [
            'prefix' => 'FAC',
            'digit_count' => 4,
            'include_year' => true,
            'last_number' => 100, // Should be ignored
        ]);

        // Verify last_number was not changed
        $this->assertDatabaseHas('quote_number_settings', [
            'user_id' => $user->id,
            'last_number' => 5, // Should still be 5
        ]);
    }

    public function test_preview_reflects_current_settings_on_load()
    {
        $user = User::factory()->create();
        QuoteNumberSetting::create([
            'user_id' => $user->id,
            'prefix' => 'TEST',
            'digit_count' => 3,
            'include_year' => true,
            'last_number' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('settings.quotes'));
        
        $year = date('Y');
        // Next number should be 6, padded to 3 digits: 006
        // Format: TEST-{YEAR}-006
        $expectedPreview = "TEST-{$year}-006";

        $response->assertInertia(fn (Assert $page) => $page
            ->where('preview', $expectedPreview)
        );
    }
}
