<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_prospect_can_be_created(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'user_id' => $user->id,
            'name' => 'John Doe',
            'company' => 'Acme Corp',
            'email' => 'john.doe@example.com',
            'phone' => '123-456-7890',
            'address' => '123 Main St',
            'status' => 'prospect',
        ];

        $prospect = Prospect::create($prospectData);

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'user_id' => $user->id,
            'name' => 'John Doe',
            'company' => 'Acme Corp',
            'status' => 'prospect',
        ]);

        // Test encrypted fields
        $this->assertEquals('john.doe@example.com', $prospect->email);
        $this->assertEquals('123-456-7890', $prospect->phone);
        $this->assertEquals('123 Main St', $prospect->address);
    }

    /** @test */
    public function a_prospect_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $prospect->user);
        $this->assertEquals($user->id, $prospect->user->id);
    }

    /** @test */
    public function encrypted_fields_are_stored_encrypted_in_the_database(): void
    {
        $user = User::factory()->create();
        $prospectData = [
            'user_id' => $user->id,
            'name' => 'Jane Smith',
            'company' => 'Widgets Inc',
            'email' => 'jane.smith@example.com',
            'phone' => '098-765-4321',
            'address' => '456 Oak Ave',
            'status' => 'prospect',
        ];

        $prospect = Prospect::create($prospectData);

        $retrievedProspect = DB::table('prospects')->where('id', $prospect->id)->first();

        // Check that the raw database value is not the original plaintext
        $this->assertNotEquals('jane.smith@example.com', $retrievedProspect->email);
        $this->assertStringStartsWith('ey', $retrievedProspect->email); // Laravel encryption prefix

        // Ensure model access decrypts correctly
        $this->assertEquals('jane.smith@example.com', $prospect->email);
    }

    /** @test */
    public function an_authenticated_user_can_create_a_prospect(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'name' => 'New Prospect',
            'company' => 'New Company Inc',
            'email' => 'new.prospect@example.com',
            'phone' => '999-888-7777',
            'address' => '789 Pine Rd',
            'status' => 'prospect', // Added status
        ]);

        $response->assertRedirect(route('clients.index')); // Assuming clients.index is the redirect target
        $response->assertSessionHas('success', 'Prospect créé avec succès.'); // Changed message to French

        $this->assertDatabaseHas('prospects', [
            'user_id' => $user->id,
            'name' => 'New Prospect',
            'company' => 'New Company Inc',
            'status' => 'prospect',
        ]);

        // Verify encrypted fields
        $prospect = Prospect::where('user_id', $user->id)->where('name', 'New Prospect')->first();
        $this->assertEquals('new.prospect@example.com', $prospect->email);
    }

    /** @test */
    public function name_and_company_are_required_to_create_a_prospect(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'email' => 'missing@example.com',
            'phone' => '111-222-3333',
            'status' => 'prospect', // Added status
        ]);

        $response->assertSessionHasErrors(['name', 'company']);
        $this->assertDatabaseCount('prospects', 0);
    }

    /** @test */
    public function email_must_be_a_valid_email_format(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'name' => 'Invalid Email Prospect',
            'company' => 'Invalid Co',
            'email' => 'invalid-email', // Invalid email format
            'status' => 'prospect', // Added status
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertDatabaseCount('prospects', 0);
    }

    /** @test */
    public function unauthenticated_users_cannot_create_prospects(): void
    {
        $response = $this->post(route('prospects.store'), [
            'name' => 'Unauthenticated Prospect',
            'company' => 'Unauth Co',
        ]);

        $response->assertRedirect(route('login')); // Assuming unauthenticated users are redirected to login
        $this->assertDatabaseCount('prospects', 0);
    }

    /** @test */
    public function an_authenticated_user_can_view_their_prospects_list(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Clients/Index')
            ->where('prospects.total', 3)
        );
    }

    /** @test */
    public function the_clients_index_page_displays_paginated_prospects_and_clients(): void
    {
        $user = User::factory()->create();
        // Create 5 prospects and 5 clients for this user
        Prospect::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'prospect']);
        Prospect::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'client']);

        // Other user's prospects should not be visible
        Prospect::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Clients/Index')
            ->has('prospects.data', 10) // 10 items per page by default in controller
            ->has('prospects.links') // Check for pagination links
            ->has('prospects.data.0.status') // Ensure status field is present
        );
    }

    /** @test */
    public function the_clients_index_page_distinguishes_between_prospects_and_clients(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'John Prospect', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Jane Client', 'status' => 'client']);

        $response = $this->actingAs($user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Clients/Index')
            ->has('prospects.data', 2)
            ->where('prospects.data.0.name', 'Jane Client')
            ->where('prospects.data.0.status', 'client')
            ->where('prospects.data.1.name', 'John Prospect')
            ->where('prospects.data.1.status', 'prospect')
        );
    }

    /** @test */
    public function a_prospect_can_be_created_with_valid_siret(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'name' => 'Test Prospect',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'siret' => '73282932000074', // Valid SIRET with Luhn checksum
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->post(route('prospects.store'), $prospectData);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('prospects', [
            'name' => 'Test Prospect',
            'company' => 'Test Company',
        ]);
    }

    /** @test */
    public function creating_a_prospect_with_invalid_siret_format_fails(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'name' => 'Test Prospect',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'siret' => '12345', // Invalid: not 14 digits
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->post(route('prospects.store'), $prospectData);

        $response->assertSessionHasErrors(['siret' => 'Le numéro SIRET doit contenir 14 chiffres.']);
        $this->assertDatabaseMissing('prospects', [
            'name' => 'Test Prospect',
        ]);
    }

    /** @test */
    public function creating_a_prospect_with_invalid_siret_luhn_fails(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'name' => 'Test Prospect',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'siret' => '12345678901234', // 14 digits but invalid Luhn checksum
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->post(route('prospects.store'), $prospectData);

        $response->assertSessionHasErrors(['siret' => "Le numéro SIRET n'est pas valide"]);
        $this->assertDatabaseMissing('prospects', [
            'name' => 'Test Prospect',
        ]);
    }

    /** @test */
    public function updating_a_prospect_with_invalid_siret_fails(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $updateData = [
            'name' => $prospect->name,
            'company' => $prospect->company,
            'email' => $prospect->email,
            'siret' => 'INVALID123', // Invalid format
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->put(route('clients.update', $prospect->id), $updateData);

        $response->assertSessionHasErrors(['siret']);
    }

    /** @test */
    public function empty_siret_is_accepted_as_optional_field(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'name' => 'Test Prospect',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'siret' => '', // Empty SIRET should be allowed
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->post(route('prospects.store'), $prospectData);

        $response->assertRedirect(route('clients.index'));
        $this->assertDatabaseHas('prospects', [
            'name' => 'Test Prospect',
        ]);
    }
}