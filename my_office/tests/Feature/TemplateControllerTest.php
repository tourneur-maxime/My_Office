<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_template()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $templateData = [
            'name' => 'Mon Super Modèle',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'font_family' => 'serif',
            'logo_path' => 'logos/test.png',
            'logo_size' => 150,
            'logo_position' => 'center',
            'is_default' => true,
        ];

        $response = $this->post(route('settings.branding.saveTemplate'), $templateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('templates', [
            'user_id' => $user->id,
            'name' => 'Mon Super Modèle',
            'primary_color' => '#FF0000',
            'font_family' => 'serif',
            'is_default' => true,
        ]);
    }

    public function test_saving_new_default_template_unsets_previous_default()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $oldDefault = Template::factory()->create([
            'user_id' => $user->id,
            'is_default' => true,
        ]);

        $templateData = [
            'name' => 'Nouveau Modèle Par Défaut',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'font_family' => 'serif',
            'logo_size' => 150,
            'logo_position' => 'center',
            'is_default' => true,
        ];

        $this->post(route('settings.branding.saveTemplate'), $templateData);

        $this->assertDatabaseHas('templates', [
            'id' => $oldDefault->id,
            'is_default' => false,
        ]);
    }

    public function test_save_template_validation_fails_with_invalid_color()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $templateData = [
            'name' => 'Modèle Invalide',
            'primary_color' => 'invalid-color',
            'secondary_color' => '#00FF00',
            'font_family' => 'serif',
            'logo_size' => 150,
            'logo_position' => 'center',
        ];

        $response = $this->post(route('settings.branding.saveTemplate'), $templateData);

        $response->assertSessionHasErrors(['primary_color']);
    }

    public function test_save_template_validation_fails_with_unsafe_font()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $templateData = [
            'name' => 'Modèle Invalide',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'font_family' => 'Comic Sans MS',
            'logo_size' => 150,
            'logo_position' => 'center',
        ];

        $response = $this->post(route('settings.branding.saveTemplate'), $templateData);

        $response->assertSessionHasErrors(['font_family']);
    }

    public function test_save_template_validation_fails_with_duplicate_name_for_same_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Template::factory()->create([
            'user_id' => $user->id,
            'name' => 'Modèle Unique',
        ]);

        $templateData = [
            'name' => 'Modèle Unique',
            'primary_color' => '#FF0000',
            'secondary_color' => '#00FF00',
            'font_family' => 'serif',
            'logo_size' => 150,
            'logo_position' => 'center',
        ];

        $response = $this->post(route('settings.branding.saveTemplate'), $templateData);

        $response->assertSessionHasErrors(['name']);
    }
}