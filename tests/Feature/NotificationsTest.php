<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_displays_a_success_toast_when_a_flash_message_is_present(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->withSession(['success' => 'Opération réussie'])
            ->get(route('dashboard'));

        // Check if flash message is in the Inertia props
        $response->assertInertia(fn ($page) => $page
            ->where('flash.success', 'Opération réussie')
        );
    }

    public function test_it_displays_an_error_toast_when_an_error_flash_message_is_present(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->withSession(['error' => 'Une erreur est survenue'])
            ->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('flash.error', 'Une erreur est survenue')
        );
    }
}