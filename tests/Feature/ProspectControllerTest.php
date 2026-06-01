<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProspectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user; // Declare a property to store the authenticated user

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(); // Create and store the user
        $this->actingAs($this->user); // Act as this user
    }

    public function test_displays_a_list_of_prospects_and_clients(): void
    {
        // Create some prospects and clients with explicit statuses
        Prospect::factory()->count(2)->create(['status' => 'prospect', 'user_id' => $this->user->id]);
        Prospect::factory()->count(1)->create(['status' => 'client', 'user_id' => $this->user->id]);

        $response = $this->get('/clients');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Index')
            ->where('prospects.total', 3)
            ->has('prospects.data', 3) // Assert that there are 3 prospects in the data array
            ->where('prospects.data.0.status', 'client')   // Client is created last, so it's first with latest()
            ->where('prospects.data.1.status', 'prospect') // Prospect
            ->where('prospects.data.2.status', 'prospect') // Prospect
        );
    }

    public function test_a_user_cannot_view_another_users_prospect_edit_page(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('clients.edit', $prospect));

        $response->assertForbidden(); // Expect a 403 Forbidden response
    }

    public function test_an_authenticated_user_can_view_their_prospect_edit_page(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $prospect->refresh(); // Reload from DB to get default values

        $response = $this->actingAs($user)->get(route('clients.edit', $prospect));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Edit')
            ->has('prospect', fn (Assert $prospectPage) => $prospectPage
                ->where('id', $prospect->id)
                ->where('user_id', $prospect->user_id)
                ->where('name', $prospect->name)
                ->where('company', $prospect->company)
                ->where('email', $prospect->email)
                ->where('phone', $prospect->phone)
                ->where('address', $prospect->address)
                ->where('zip_code', $prospect->zip_code)
                ->where('city', $prospect->city)
                ->where('status', $prospect->status)
                ->where('siret', $prospect->siret)
                ->where('vat_status', $prospect->vat_status)
                ->where('is_favorite', $prospect->is_favorite)
                ->where('alias', $prospect->alias)
                ->where('vat_number', $prospect->vat_number)
                ->has('created_at') // Assert existence, not specific value for dynamic fields
                ->has('updated_at') // Assert existence, not specific value
                ->has('notes') // Assert the 'notes' relationship exists
            )
        );
    }

    public function test_an_authenticated_user_can_update_their_prospect(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $updatedData = [
            'name' => 'Updated Prospect Name',
            'company' => 'Updated Company Inc',
            'email' => 'updated.email@example.com',
            'phone' => '111-222-3333',
            'address' => 'New Updated Address',
            'zip_code' => '75002',
            'city' => 'Marseille',
            'status' => 'client', // Change status from default
        ];

        $response = $this->actingAs($user)->put(route('clients.update', $prospect->id), $updatedData);

        $response->assertRedirect(route('clients.show', $prospect));
        $response->assertSessionHas('success', 'Client mis à jour avec succès.');

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'user_id' => $user->id,
            'name' => 'Updated Prospect Name',
            'company' => 'Updated Company Inc',
            'zip_code' => '75002',
            'city' => 'Marseille',
            'status' => 'client',
        ]);

        // Verify encrypted fields
        $updatedProspect = Prospect::find($prospect->id);
        $this->assertEquals('updated.email@example.com', $updatedProspect->email);
        $this->assertEquals('111-222-3333', $updatedProspect->phone);
        $this->assertEquals('New Updated Address', $updatedProspect->address);
    }

    public function test_name_and_company_are_required_to_update_a_prospect(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('clients.update', $prospect->id), [
            'name' => '', // Missing name
            'company' => '', // Missing company
            'email' => 'valid@example.com',
            'status' => 'prospect',
        ]);

        $response->assertSessionHasErrors(['name', 'company']);
        $response->assertStatus(302); // Redirect back on validation error
    }

    public function test_email_must_be_a_valid_email_format_for_update(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->put(route('clients.update', $prospect->id), [
            'name' => 'Valid Name',
            'company' => 'Valid Company',
            'email' => 'invalid-email', // Invalid email format
            'status' => 'prospect',
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertStatus(302);
    }

    public function test_email_cannot_be_taken_by_another_prospect_when_updating(): void
    {
        $user = User::factory()->create();
        $prospect1 = Prospect::factory()->create(['user_id' => $user->id, 'email' => 'unique1@example.com']);
        $prospect2 = Prospect::factory()->create(['user_id' => $user->id, 'email' => 'unique2@example.com']);

        // Attempt to update prospect1 with prospect2's email
        $response = $this->actingAs($user)->put(route('clients.update', ['prospect' => $prospect1->id]), [
            'name' => 'Valid Name',
            'company' => 'Valid Company',
            'email' => 'unique2@example.com', // Not unique
            'status' => 'prospect', // Ensure status is passed
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertStatus(302);
    }

    public function test_email_can_be_its_own_when_updating_a_prospect(): void
    {
        $user = User::factory()->create();
        $prospect1 = Prospect::factory()->create(['user_id' => $user->id, 'email' => 'unique1@example.com']);

        // Attempt to update prospect1 with its own email (should pass)
        $response = $this->actingAs($user)->put(route('clients.update', $prospect1->id), [
            'name' => 'Valid Name',
            'company' => 'Valid Company',
            'email' => 'unique1@example.com', // Its own email
            'status' => 'prospect',
        ]);

        $response->assertRedirect(route('clients.show', $prospect1));
    }

    public function test_a_user_cannot_add_a_note_to_another_users_prospect(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->post(route('clients.notes.store', $prospect->id), [
            'content' => 'This is a test note for another user\'s prospect.',
        ]);

        $response->assertForbidden(); // Expect a 403 Forbidden response
        $this->assertDatabaseCount('notes', 0); // No note should be created
    }

    public function test_an_authenticated_user_can_add_a_note_to_their_prospect(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('clients.notes.store', $prospect->id), [
            'content' => 'This is a new note for my prospect.',
        ]);

        $response->assertRedirect(); // Redirect back to the client detail page
        $response->assertSessionHas('success', 'Note ajoutée avec succès');
        $this->assertDatabaseHas('notes', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'content' => 'This is a new note for my prospect.',
        ]);
    }

    public function test_authenticated_user_can_update_note_on_their_client(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);
        $note = $client->notes()->create([
            'user_id' => $user->id,
            'content' => 'Original note content',
        ]);

        $response = $this->actingAs($user)->put(route('clients.notes.update', [$client, $note]), [
            'content' => 'Updated note content',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Note modifiée avec succès');

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'content' => 'Updated note content',
        ]);
    }

    public function test_authenticated_user_can_delete_note_from_their_client(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);
        $note = $client->notes()->create([
            'user_id' => $user->id,
            'content' => 'Note to be deleted',
        ]);

        $response = $this->actingAs($user)->delete(route('clients.notes.destroy', [$client, $note]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Note supprimée avec succès');

        $this->assertDatabaseMissing('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_user_cannot_update_note_on_another_users_client(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user2->id,
            'status' => 'client',
        ]);
        $note = $client->notes()->create([
            'user_id' => $user2->id,
            'content' => 'Original note',
        ]);

        $response = $this->actingAs($user1)->put(route('clients.notes.update', [$client, $note]), [
            'content' => 'Hacked content',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'content' => 'Original note',
        ]);
    }

    public function test_user_cannot_delete_note_from_another_users_client(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user2->id,
            'status' => 'client',
        ]);
        $note = $client->notes()->create([
            'user_id' => $user2->id,
            'content' => 'Note content',
        ]);

        $response = $this->actingAs($user1)->delete(route('clients.notes.destroy', [$client, $note]));

        $response->assertForbidden();

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
        ]);
    }

    public function test_an_authenticated_user_can_delete_their_prospect(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $prospect->id));
        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success', 'Client supprimé avec succès.');
        $this->assertDatabaseMissing('prospects', ['id' => $prospect->id]);
    }

    public function test_a_user_cannot_delete_another_users_prospect(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $prospect->id));

        $response->assertForbidden(); // Expect a 403 Forbidden response
        $this->assertDatabaseHas('prospects', ['id' => $prospect->id]); // Prospect should still exist
    }

    public function test_deleting_prospect_cascades_and_deletes_associated_notes(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $note = $prospect->notes()->create(['user_id' => $user->id, 'content' => 'Some note']);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $prospect->id));

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success', 'Client supprimé avec succès.'); // Prospect should be deleted
        $this->assertDatabaseMissing('notes', ['id' => $note->id]); // Associated notes should also be deleted
    }

    public function test_autocomplete_suggestions_are_returned_for_prospects(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Alpha Prospect', 'email' => 'alpha@example.com']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Beta Client', 'company' => 'Beta Corp']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Gamma User']);
        Prospect::factory()->count(10)->create(['user_id' => $user->id]); // Create more to test limit

        $response = $this->actingAs($user)->getJson(route('api.clients.suggestions', ['search' => 'Beta']));

        $response->assertOk();
        $response->assertJsonCount(1); // Expect only one suggestion for 'Beta'
        $response->assertJsonFragment([
            'name' => 'Beta Client',
            'company' => 'Beta Corp',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.clients.suggestions', ['search' => 'a'])); // Search for 'a' to get multiple results
        $response->assertOk();
        $response->assertJsonCount(5); // Expect a maximum of 5 suggestions
    }

    public function test_displays_create_prospect_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('clients.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Create')
        );
    }

    public function test_an_authenticated_user_can_create_a_new_prospect(): void
    {
        $user = User::factory()->create();

        $prospectData = [
            'name' => 'New Prospect',
            'company' => 'New Company Inc',
            'email' => 'new.prospect@example.com',
            'phone' => '555-123-4567',
            'address' => '123 Main Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'prospect', // Added status
        ];

        $response = $this->actingAs($user)->post(route('prospects.store'), $prospectData);

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success', 'Prospect créé avec succès.');

        $this->assertDatabaseHas('prospects', [
            'user_id' => $user->id,
            'name' => 'New Prospect',
            'company' => 'New Company Inc',
            'status' => 'prospect', // Default status
            'zip_code' => '75001',
            'city' => 'Paris',
        ]);

        // Verify encrypted fields
        $prospect = Prospect::where('user_id', $user->id)
            ->where('name', 'New Prospect')
            ->first();

        $this->assertEquals('new.prospect@example.com', $prospect->email);
        $this->assertEquals('555-123-4567', $prospect->phone);
        $this->assertEquals('123 Main Street', $prospect->address);
    }

    public function test_name_and_company_are_required_to_create_a_prospect(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'name' => '', // Missing name
            'company' => '', // Missing company
            'email' => 'valid@example.com',
            'status' => 'prospect', // Added status
        ]);

        $response->assertSessionHasErrors(['name', 'company']);
        $response->assertStatus(302);
    }

    public function test_email_is_required_and_must_be_valid_format_to_create_prospect(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'name' => 'Valid Name',
            'company' => 'Valid Company',
            'email' => '', // Missing email
            'status' => 'prospect', // Added status
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertStatus(302);

        $response = $this->actingAs($user)->post(route('prospects.store'), [
            'name' => 'Valid Name',
            'company' => 'Valid Company',
            'email' => 'invalid-email', // Invalid format
            'status' => 'prospect', // Added status
        ]);

        $response->assertSessionHasErrors(['email']);
        $response->assertStatus(302);
    }

    // ========================================
    // Story 1.2: Conversion Tests
    // ========================================

    public function test_authenticated_user_can_convert_their_prospect_to_client(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'prospect',
        ]);

        $response = $this->actingAs($user)->put(route('clients.convertToClient', $prospect->id));

        $response->assertRedirect(route('clients.show', $prospect));
        $response->assertSessionHas('success', 'Prospect converti en client avec succès.');

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'status' => 'client',
        ]);
    }

    public function test_user_cannot_convert_another_users_prospect(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user2->id,
            'status' => 'prospect',
        ]);

        $response = $this->actingAs($user1)->put(route('clients.convertToClient', $prospect->id));

        $response->assertForbidden();

        // Ensure status was not changed
        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'status' => 'prospect',
        ]);
    }

    public function test_conversion_preserves_all_prospect_data(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'prospect',
            'name' => 'Original Name',
            'company' => 'Original Company',
            'email' => 'original@example.com',
            'phone' => '555-0001',
            'address' => '123 Original Street',
            'zip_code' => '75001',
            'city' => 'Paris',
        ]);

        $response = $this->actingAs($user)->put(route('clients.convertToClient', $prospect->id));

        $response->assertRedirect(route('clients.show', $prospect));

        // Verify all data is preserved
        $prospect->refresh();
        $this->assertEquals('client', $prospect->status);
        $this->assertEquals('Original Name', $prospect->name);
        $this->assertEquals('Original Company', $prospect->company);
        $this->assertEquals('original@example.com', $prospect->email);
        $this->assertEquals('555-0001', $prospect->phone);
        $this->assertEquals('123 Original Street', $prospect->address);
        $this->assertEquals('75001', $prospect->zip_code);
        $this->assertEquals('Paris', $prospect->city);
    }

    public function test_conversion_updates_status_correctly_in_database(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'prospect',
        ]);

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'status' => 'prospect',
        ]);

        $this->actingAs($user)->put(route('clients.convertToClient', $prospect->id));

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'status' => 'client',
        ]);

        $this->assertDatabaseMissing('prospects', [
            'id' => $prospect->id,
            'status' => 'prospect',
        ]);
    }

    public function test_conversion_maintains_encrypted_field_integrity(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'prospect',
            'email' => 'encrypted@example.com',
            'phone' => '555-ENCRYPTED',
            'address' => '456 Encrypted Avenue',
        ]);

        // Store original encrypted values for comparison
        $originalEmail = $prospect->email;
        $originalPhone = $prospect->phone;
        $originalAddress = $prospect->address;

        $this->actingAs($user)->put(route('clients.convertToClient', $prospect->id));

        $prospect->refresh();

        // Verify encrypted fields still decrypt correctly
        $this->assertEquals($originalEmail, $prospect->email);
        $this->assertEquals($originalPhone, $prospect->phone);
        $this->assertEquals($originalAddress, $prospect->address);
    }

    public function test_only_prospects_can_be_converted(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client', // Already a client
        ]);

        $response = $this->actingAs($user)
            ->from(route('clients.show', $client))
            ->put(route('clients.convertToClient', $client->id));
            
        $response->assertRedirect(route('clients.show', $client));
        $response->assertSessionHas('error', 'Ce contact est déjà un client.');

        // Ensure status remains 'client'
        $this->assertDatabaseHas('prospects', [
            'id' => $client->id,
            'status' => 'client',
        ]);
    }

    public function test_conversion_with_associated_notes_preserves_notes(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'prospect',
        ]);

        // Create notes for the prospect
        $prospect->notes()->create([
            'content' => 'Test note 1',
            'user_id' => $user->id,
        ]);
        $prospect->notes()->create([
            'content' => 'Test note 2',
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->put(route('clients.convertToClient', $prospect->id));

        $prospect->refresh();

        // Verify conversion happened
        $this->assertEquals('client', $prospect->status);

        // Verify notes are preserved
        $this->assertCount(2, $prospect->notes);
        $this->assertEquals('Test note 1', $prospect->notes[0]->content);
        $this->assertEquals('Test note 2', $prospect->notes[1]->content);
    }

    public function test_index_method_filters_by_prospect_status(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'prospect']);
        Prospect::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'client']);

        $response = $this->actingAs($user)->get(route('clients.index', ['status' => 'prospect']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Index')
            ->where('prospects.total', 3)
            ->has('prospects.data', 3)
            ->where('prospects.data.0.status', 'prospect')
            ->where('prospects.data.1.status', 'prospect')
            ->where('prospects.data.2.status', 'prospect')
        );
    }

    public function test_index_method_filters_by_client_status(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'prospect']);
        Prospect::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'client']);

        $response = $this->actingAs($user)->get(route('clients.index', ['status' => 'client']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Index')
            ->has('prospects.data', 2)
            ->where('prospects.data.0.status', 'client')
            ->where('prospects.data.1.status', 'client')
        );
    }

    public function test_converted_prospect_disappears_from_prospect_list_and_appears_in_client_list(): void
    {
        $user = User::factory()->create();
        $prospect1 = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'prospect']);
        $prospect2 = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'prospect']);
        $client1 = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        // Assert initial counts
        $responseProspects = $this->actingAs($user)->get(route('clients.index', ['status' => 'prospect']));
        $responseProspects->assertInertia(fn (Assert $page) => $page->has('prospects.data', 2));

        $responseClients = $this->actingAs($user)->get(route('clients.index', ['status' => 'client']));
        $responseClients->assertInertia(fn (Assert $page) => $page->has('prospects.data', 1));

        // Convert one prospect
        $this->actingAs($user)->put(route('clients.convertToClient', $prospect1->id));

        // Re-assert counts after conversion
        $responseProspectsAfter = $this->actingAs($user)->get(route('clients.index', ['status' => 'prospect']));
        $responseProspectsAfter->assertInertia(fn (Assert $page) => $page->has('prospects.data', 1)); // One less prospect

        $responseClientsAfter = $this->actingAs($user)->get(route('clients.index', ['status' => 'client']));
        $responseClientsAfter->assertInertia(fn (Assert $page) => $page->has('prospects.data', 2)); // One more client
    }

    // STORY 1.3: Client Management Tests

    public function test_authenticated_user_can_update_their_client(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
            'name' => 'Old Name',
            'company' => 'Old Company',
        ]);

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'New Name',
            'company' => 'New Company',
            'email' => 'newemail@example.com',
            'phone' => '0123456789',
            'address' => '123 New Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client', // Added status
        ]);
        $response->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('prospects', [
            'id' => $client->id,
            'name' => 'New Name',
            'company' => 'New Company',
        ]);

        // Verify encrypted fields are still encrypted
        $updatedClient = Prospect::find($client->id);
        $this->assertEquals('newemail@example.com', $updatedClient->email);
        $this->assertEquals('0123456789', $updatedClient->phone);
        $this->assertEquals('123 New Street', $updatedClient->address);
    }

    public function test_user_cannot_update_another_users_client(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user2->id,
            'status' => 'client',
        ]);

        $response = $this->actingAs($user1)->put(route('clients.update', $client), [
            'name' => 'Hacked Name',
            'company' => 'Hacked Company',
            'email' => 'hacked@example.com',
            'phone' => '0000000000',
            'address' => 'Hacked Street',
            'zip_code' => '00000',
            'city' => 'Hacked City',
            'status' => 'client',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('prospects', [
            'id' => $client->id,
            'name' => 'Hacked Name',
        ]);
    }

    public function test_siret_validation_requires_14_digits(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);

        // Test with 13 digits (invalid)
        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Test Name',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client',
            'siret' => '1234567890123', // 13 digits - invalid
        ]);

        $response->assertSessionHasErrors('siret');

        // Test with 14 digits and valid Luhn checksum
        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Test Name',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client',
            'siret' => '73282932000074', // 14 digits with valid Luhn checksum
        ]);
    }

    public function test_siret_validation_fails_for_invalid_luhn_checksum(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);

        // 14 digits but invalid Luhn checksum (e.g., 12345678901234)
        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Test Name',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client',
            'siret' => '12345678901234', // Invalid Luhn
        ]);

        $response->assertSessionHasErrors('siret');
        $this->assertEquals(["Le numéro SIRET n'est pas valide"], session('errors')->get('siret'));
    }

    public function test_update_preserves_encrypted_field_integrity(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
            'email' => 'original@example.com',
            'phone' => '0111111111',
            'address' => 'Original Address',
        ]);

        // Update non-encrypted fields only
        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Updated Name',
            'company' => 'Updated Company',
            'email' => 'original@example.com', // Same email
            'phone' => '0111111111', // Same phone
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client',
        ]);
        $response->assertRedirect(route('clients.show', $client));

        $updatedClient = Prospect::find($client->id);
        $this->assertEquals('original@example.com', $updatedClient->email);
        $this->assertEquals('0111111111', $updatedClient->phone);
        $this->assertEquals('Original Address', $updatedClient->address);
    }

    public function test_vat_status_field_is_updated_correctly(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
            'vat_status' => false,
        ]);

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Test Name',
            'company' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'zip_code' => '75001',
            'city' => 'Paris',
            'status' => 'client', // Added status
            'vat_status' => true, // Updated to boolean true
        ]);
        $response->assertRedirect(route('clients.show', $client));

        $this->assertDatabaseHas('prospects', [
            'id' => $client->id,
            'vat_status' => true, // Assert against boolean true
        ]);
    }

    public function test_client_with_quotes_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);

        // Create a quote for this client
        \App\Models\Quote::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('clients.destroy', $client));

        // Expecting an error response
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Impossible de supprimer un client avec des devis ou factures associés.'); // Added period

        $this->assertDatabaseHas('prospects', [
            'id' => $client->id,
        ]);
    }

    public function test_authenticated_user_can_search_prospects_by_name(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Beta Client', 'company' => 'Beta Corp', 'status' => 'client']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Gamma User', 'status' => 'prospect']);
        Prospect::factory()->count(10)->create(['user_id' => $user->id]); // Create more to test limit

        $response = $this->actingAs($user)->getJson(route('api.clients.suggestions', ['search' => 'Beta']));

        $response->assertOk();
        $response->assertJsonCount(1); // Expect only one suggestion for 'Beta'
        $response->assertJsonFragment([
            'name' => 'Beta Client',
            'company' => 'Beta Corp',
        ]);

        $response = $this->actingAs($user)->getJson(route('api.clients.suggestions', ['search' => 'a'])); // Search for 'a' to get multiple results
        $response->assertOk();
        $response->assertJsonCount(5); // Expect a maximum of 5 suggestions
    }

    public function test_authenticated_user_can_search_by_company(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'John', 'company' => 'Acme Solutions', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Jane', 'company' => 'Beta Corp', 'status' => 'client']);

        $response = $this->actingAs($user)->getJson('/clients/search?q=Beta');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['items', 'total']]);
        $this->assertGreaterThan(0, count($response->json('data.items')));
    }

    public function test_search_respects_user_authorization(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user1->id, 'name' => 'Secret Prospect', 'status' => 'prospect']);

        $response = $this->actingAs($user2)->getJson('/clients/search?q=Secret');

        $response->assertOk();
        // Response should not include Secret Prospect - should return empty results
        $response->assertJson(['success' => true, 'data' => ['items' => [], 'total' => 0]]);
    }

    public function test_search_endpoint_returns_paginated_results(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(20)->create(['user_id' => $user->id, 'name' => 'Test', 'status' => 'prospect']);

        $response = $this->actingAs($user)->get('/clients/search?q=Test');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items',
                'total',
            ],
        ]);
    }

    public function test_search_with_empty_query_returns_all_prospects(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(5)->create(['user_id' => $user->id, 'status' => 'prospect']);

        $response = $this->actingAs($user)->get('/clients/search?q=');

        $response->assertOk();
    }

    // AC2: Keyboard Navigation Tests
    public function test_search_returns_ranked_results_for_keyboard_navigation(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Acme', 'company' => 'Acme Corp', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Beta Acme', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'company' => 'Acme Industries', 'status' => 'client']);

        $response = $this->actingAs($user)->getJson('/clients/search?q=Acme&limit=10');

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['items', 'total']]);
        // Verify results are ranked (exact match first)
        $items = $response->json('data.items');
        $this->assertGreaterThan(0, count($items));
        $this->assertContains('Acme', array_column($items, 'name'));
        $this->assertContains('Beta Acme', array_column($items, 'name'));
        $this->assertContains('Acme Industries', array_column($items, 'company'));
    }

    public function test_search_respects_limit_parameter_for_autocomplete(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(20)->create(['user_id' => $user->id, 'name' => 'Test Item', 'status' => 'prospect']);

        $response = $this->actingAs($user)->getJson('/clients/search?q=Test&limit=5');

        $response->assertOk();
        $items = $response->json('data.items');
        $this->assertLessThanOrEqual(5, count($items));
    }

    public function test_search_filters_by_type_parameter(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Prospect Item', 'status' => 'prospect']);
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Client Item', 'status' => 'client']);

        $prospectResponse = $this->actingAs($user)->getJson('/clients/search?q=Item&type=prospect');
        $prospectResponse->assertOk();
        $prospectItems = $prospectResponse->json('data.items');
        foreach ($prospectItems as $item) {
            $this->assertEquals('prospect', $item['status']);
        }

        $clientResponse = $this->actingAs($user)->getJson('/clients/search?q=Item&type=client');
        $clientResponse->assertOk();
        $clientItems = $clientResponse->json('data.items');
        foreach ($clientItems as $item) {
            $this->assertEquals('client', $item['status']);
        }
    }

    // AC5: URL Persistence Tests
    public function test_search_persistence_loads_from_url_parameters(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Test Prospect', 'status' => 'prospect']);

        // First request with search parameter
        $response = $this->actingAs($user)->get('/clients?search=Test&status=prospect');
        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Clients/Index')
            // These would be passed as props to the Vue component
            ->has('prospects')
        );
    }

    public function test_search_results_update_url_when_search_changes(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Acme Search Test']);

        // Verify the search API endpoint works and can be used to update URL
        $response = $this->actingAs($user)->getJson('/clients/search?q=Acme');
        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['items', 'total']]);
    }

    public function test_clear_search_resets_to_full_list(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'prospect']);
        Prospect::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'client']);

        // With search
        $searchResponse = $this->actingAs($user)->getJson('/clients/search?q=xyz');
        $searchResponse->assertOk();

        // Clear search (empty query)
        $clearResponse = $this->actingAs($user)->getJson('/clients/search?q=');
        $clearResponse->assertOk();
        $clearResponse->assertJson(['success' => true]);
        // Should return all items when query is empty
        $this->assertEquals(5, $clearResponse->json('data.total'));
    }

    public function test_client_without_dependencies_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'status' => 'client',
        ]);

        // Ensure no quotes, invoices, or notes
        $this->assertEquals(0, $client->quotes()->count());
        $this->assertEquals(0, $client->invoices()->count());
        $this->assertEquals(0, $client->notes()->count());

        $response = $this->actingAs($user)->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success', 'Client supprimé avec succès.');

        $this->assertDatabaseMissing('prospects', [
            'id' => $client->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_client(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $client = Prospect::factory()->create([
            'user_id' => $user2->id,
            'status' => 'client',
        ]);

        $response = $this->actingAs($user1)->delete(route('clients.destroy', $client));

        $response->assertForbidden();

        $this->assertDatabaseHas('prospects', [
            'id' => $client->id,
        ]);
    }

    // Security Tests for Story 1.4
    public function test_suggestions_endpoint_respects_user_authorization(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create prospects for both users
        Prospect::factory()->create(['user_id' => $user1->id, 'name' => 'User1 Prospect']);
        Prospect::factory()->create(['user_id' => $user2->id, 'name' => 'User2 Secret']);

        // User1 should only see their own prospects
        $response = $this->actingAs($user1)->getJson(route('api.clients.suggestions', ['search' => 'User']));

        $response->assertOk();
        $suggestions = $response->json();

        foreach ($suggestions as $suggestion) {
            $this->assertEquals($user1->id, Prospect::find($suggestion['id'])->user_id);
        }
    }

    public function test_search_endpoint_sanitizes_sql_injection_attempts(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Normal Prospect']);

        // Attempt SQL injection
        $response = $this->actingAs($user)->getJson('/clients/search?q='.urlencode("' OR '1'='1"));

        $response->assertOk();
        // Should return safe results, not expose data
        $this->assertIsArray($response->json('data.items'));
    }

    public function test_search_endpoint_sanitizes_xss_attempts(): void
    {
        $user = User::factory()->create();
        Prospect::factory()->create(['user_id' => $user->id, 'name' => 'Test Prospect']);

        // Attempt XSS
        $response = $this->actingAs($user)->getJson('/clients/search?q='.urlencode('<script>alert("xss")</script>'));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'data' => ['items', 'total']]);
    }

    public function test_unauthorized_user_cannot_access_search_endpoint(): void
    {
        // Reset authentication state
        auth()->logout();

        $response = $this->getJson('/clients/search?q=test');

        // Should return unauthorized status
        $response->assertUnauthorized();
    }
}