<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstUserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigns_owner_role_to_the_first_registered_user(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->post('/register', [
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'K#9pL2m$5vR!z8Wx',
            'password_confirmation' => 'K#9pL2m$5vR!z8Wx',
        ]);

        if (session('errors')) {
            dump(session('errors')->getMessages());
        }

        $this->assertAuthenticated();
        $user = User::first();
        $this->assertTrue($user->hasRole('Owner'));
    }

    public function test_does_not_assign_owner_role_to_the_second_registered_user(): void
    {
        // First user
        User::factory()->create();
        
        // Ensure 'Owner' role exists
        Role::create(['name' => 'Owner']);

        $response = $this->post('/register', [
            'name' => 'Second User',
            'email' => 'second@example.com',
            'password' => 'K#9pL2m$5vR!z8Wx',
            'password_confirmation' => 'K#9pL2m$5vR!z8Wx',
        ]);

        $this->assertAuthenticated();
        $user = User::where('email', 'second@example.com')->first();
        $this->assertFalse($user->hasRole('Owner'));
    }
}