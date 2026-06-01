<?php

namespace Tests\Browser;

use App\Models\Invoice; // Assuming Prospect model is used as Client
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientHistoryBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function unauthenticated_user_cannot_view_client_history_page()
    {
        $client = Prospect::factory()->create();

        $this->browse(function (Browser $browser) use ($client) {
            $browser->visit(route('clients.show', $client))
                    // Dusk middleware test - verify user is not authorized to view prospect details
                ->assertDontSee($client->business_name); // Unauthenticated user shouldn't see prospect details
        });
    }

    /** @test */
    public function authenticated_user_can_view_client_history_page_and_data()
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        Quote::factory()->count(2)->create(['client_id' => $client->id, 'user_id' => $user->id]);
        Invoice::factory()->count(3)->create(['client_id' => $client->id, 'user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user, $client) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $client))
                ->waitForText('Détails du Client', 10)
                ->assertSee($client->business_name)
                ->assertSee('Historique Commercial')
                ->assertSee('Devis')
                ->assertSee('Factures');
        });
    }

    /** @test */
    public function client_history_quotes_can_be_filtered_by_status()
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        Quote::factory()->create(['client_id' => $client->id, 'user_id' => $user->id, 'status' => 'Approuvé']);
        Quote::factory()->create(['client_id' => $client->id, 'user_id' => $user->id, 'status' => 'Brouillon']);

        $this->browse(function (Browser $browser) use ($user, $client) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $client))
                ->waitForText('Devis', 10)
                ->select('select[v-model="quoteFilterStatus"]', 'Approuvé')
                ->pause(1000) // Wait for filter to apply
                ->assertSeeIn('table tbody tr:first-child', 'Approuvé');
        });
    }

    /** @test */
    public function client_history_invoices_can_be_sorted_by_total()
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['client_id' => $client->id, 'user_id' => $user->id, 'total' => 100]);
        Invoice::factory()->create(['client_id' => $client->id, 'user_id' => $user->id, 'total' => 200]);

        $this->browse(function (Browser $browser) use ($user, $client) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $client))
                ->waitForText('Factures', 10)
                ->click('button:contains("Total")') // Click the sort button for total
                ->pause(1000) // Wait for sort to apply
                ->assertSeeIn('table tbody tr:first-child', '200'); // Expect 200 to be first if sorted desc
        });
    }
}
