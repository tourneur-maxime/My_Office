<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase; // For API authentication

class ProspectHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure a user exists for authentication
        $this->user = User::factory()->create();
    }

    /** @test */
    public function unauthenticated_user_cannot_access_prospect_history()
    {
        $prospect = Prospect::factory()->create();

        $this->getJson(route('api.prospects.history', ['prospect' => $prospect->id]))
            ->assertStatus(401); // Unauthorized
    }

    /** @test */
    public function user_cannot_access_other_users_prospect_history()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user1->id]);

        \Laravel\Sanctum\Sanctum::actingAs($user2);

        $this->getJson(route('api.prospects.history', ['prospect' => $prospect->id]))
            ->assertStatus(403); // Forbidden - cross-user access denied
    }

    /** @test */
    public function authenticated_user_can_access_their_own_prospect_history()
    {
        Sanctum::actingAs($this->user);

        $prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
        Quote::factory()->count(2)->create(['client_id' => $prospect->id, 'user_id' => $this->user->id]);
        Invoice::factory()->count(3)->create(['client_id' => $prospect->id, 'user_id' => $this->user->id]);

        $response = $this->getJson(route('api.prospects.history', ['prospect' => $prospect->id]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quotes' => [
                        '*' => ['id', 'quote_number', 'status', 'total', 'expires_at', 'created_at'],
                    ],
                    'invoices' => [
                        '*' => ['id', 'invoice_number', 'status', 'total', 'due_date', 'created_at'],
                    ],
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Historique du prospect récupéré avec succès.',
            ]);

        $this->assertCount(2, $response->json('data.quotes'));
        $this->assertCount(3, $response->json('data.invoices'));
    }

    /** @test */
    public function prospect_history_can_be_filtered_by_quote_status()
    {
        Sanctum::actingAs($this->user);

        $prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'status' => 'Approuvé']);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'status' => 'Brouillon']);

        $response = $this->getJson(route('api.prospects.history', ['prospect' => $prospect->id, 'quote_status' => 'Approuvé']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.quotes')
            ->assertJsonPath('data.quotes.0.status', 'Approuvé');
    }

    /** @test */
    public function prospect_history_can_be_filtered_by_invoice_status()
    {
        Sanctum::actingAs($this->user);

        $prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'status' => 'Payé']);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'status' => 'Brouillon']);

        $response = $this->getJson(route('api.prospects.history', ['prospect' => $prospect->id, 'invoice_status' => 'Payé']));

        $response->assertOk()
            ->assertJsonCount(1, 'data.invoices')
            ->assertJsonPath('data.invoices.0.status', 'Payé');
    }

    /** @test */
    public function prospect_history_quotes_can_be_sorted_by_total_desc()
    {
        Sanctum::actingAs($this->user);

        $prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'total' => 100]);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'total' => 200]);

        $response = $this->getJson(route('api.prospects.history', [
            'prospect' => $prospect->id,
            'quote_sort_by' => 'total',
            'quote_sort_direction' => 'desc',
        ]));

        $response->assertOk();
        $this->assertEquals(200.0, (float) $response->json('data.quotes.0.total'));
    }

    /** @test */
    public function prospect_history_invoices_can_be_sorted_by_total_asc()
    {
        Sanctum::actingAs($this->user);

        $prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'total' => 300]);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $this->user->id, 'total' => 150]);

        $response = $this->getJson(route('api.prospects.history', [
            'prospect' => $prospect->id,
            'invoice_sort_by' => 'total',
            'invoice_sort_direction' => 'asc',
        ]));

        $response->assertOk();
        $this->assertEquals(150.0, (float) $response->json('data.invoices.0.total'));
    }
}
