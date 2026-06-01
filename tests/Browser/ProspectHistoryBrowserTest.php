<?php

namespace Tests\Browser;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProspectHistoryBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function unauthenticated_user_cannot_view_prospect_history_page()
    {
        $prospect = Prospect::factory()->create();

        $this->browse(function (Browser $browser) use ($prospect) {
            $browser->visit(route('clients.show', $prospect))
                    // Dusk middleware test - verify user is not authorized to view prospect details
                ->assertDontSee($prospect->business_name); // Unauthenticated user shouldn't see prospect details
        });
    }

    /** @test */
    public function authenticated_user_can_view_prospect_history_page_and_data()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        Quote::factory()->count(2)->create(['client_id' => $prospect->id, 'user_id' => $user->id]);
        Invoice::factory()->count(3)->create(['client_id' => $prospect->id, 'user_id' => $user->id]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $prospect))
                ->waitForText('Détails du Client', 10)
                ->assertSee($prospect->business_name)
                ->assertSee('Historique Commercial')
                ->assertSee('Devis')
                ->assertSee('Factures');
        });
    }

    /** @test */
    public function prospect_history_quotes_can_be_filtered_by_status()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $user->id, 'status' => 'Approuvé']);
        Quote::factory()->create(['client_id' => $prospect->id, 'user_id' => $user->id, 'status' => 'Brouillon']);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $prospect))
                ->waitForText('Devis', 10)
                ->select('select.select-bordered', 'Approuvé')
                ->waitFor('table tbody tr', 5)
                ->assertSeeIn('table tbody tr:first-child', 'Approuvé');
        });
    }

    /** @test */
    public function prospect_history_invoices_can_be_sorted_by_total()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $user->id, 'total' => 100]);
        Invoice::factory()->create(['client_id' => $prospect->id, 'user_id' => $user->id, 'total' => 200]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit(route('clients.show', $prospect))
                ->waitForText('Factures', 10)
                ->click('button.btn-ghost:nth-of-type(3)') // Click the sort button for total (3rd button)
                ->waitFor('table tbody tr', 5)
                ->assertSeeIn('table tbody tr:first-child', '200'); // Expect 200 to be first if sorted desc
        });
    }
}
