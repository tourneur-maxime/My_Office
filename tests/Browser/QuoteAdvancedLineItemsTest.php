<?php

namespace Tests\Browser;

use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuoteAdvancedLineItemsTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function a_user_can_manage_and_reorder_quote_line_items()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id, 'company' => 'Target Client']);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit(route('quotes.create'))
                ->waitForText('Créer un devis');

            // Select a client
            $browser->type('#search-input', $prospect->company)
                ->waitFor('#suggestion-0', 5)
                ->click('#suggestion-0')
                ->waitForText('Client sélectionné');

            // Add three line items
            $browser->within('form', function (Browser $form) use ($prospect) {
                // Line 0
                $form->type('#description-0', 'Item 1');
                $form->script([
                    "document.getElementById('quantity-0').value = '1.5'",
                    "document.getElementById('quantity-0').dispatchEvent(new Event('input'))",
                    "document.getElementById('unit_price-0').value = '100'",
                    "document.getElementById('unit_price-0').dispatchEvent(new Event('input'))",
                ]);

                // Add Line 1
                $form->click('#add-line-item-btn')
                    ->waitFor('#description-1')
                    ->type('#description-1', 'Item 2');
                $form->script([
                    "document.getElementById('quantity-1').value = '1'",
                    "document.getElementById('quantity-1').dispatchEvent(new Event('input'))",
                    "document.getElementById('unit_price-1').value = '200.50'",
                    "document.getElementById('unit_price-1').dispatchEvent(new Event('input'))",
                ]);

                // Add Line 2
                $form->click('#add-line-item-btn')
                    ->waitFor('#description-2')
                    ->type('#description-2', 'Item 3');
                $form->script([
                    "document.getElementById('quantity-2').value = '1'",
                    "document.getElementById('quantity-2').dispatchEvent(new Event('input'))",
                    "document.getElementById('unit_price-2').value = '300'",
                    "document.getElementById('unit_price-2').dispatchEvent(new Event('input'))",
                ]);

                $form->pause(500); // Wait for reactivity

                $form->assertSee('650.50');

                // Reorder: Move "Item 1" (index 0) down
                $form->click('div[data-index="0"] button[title="Déplacer vers le bas"]')
                    ->pause(500);

                $form->assertInputValue('#description-0', 'Item 2')
                    ->assertInputValue('#description-1', 'Item 1');

                // Reorder: Move "Item 3" (index 2) up
                $form->click('div[data-index="2"] button[title="Déplacer vers le haut"]')
                    ->pause(500);

                $form->assertInputValue('#description-1', 'Item 3')
                    ->assertInputValue('#description-2', 'Item 1');

                // Delete index 1 ("Item 3")
                $form->click('div[data-index="1"] button[title="Supprimer cette ligne"]')
                    ->pause(500);

                $form->assertInputValue('#description-0', 'Item 2')
                    ->assertInputValue('#description-1', 'Item 1')
                    ->assertSee('350.50');

                $form->click('#submit-quote-btn');
                $form->waitForRoute('clients.edit', ['prospect' => $prospect->id]);
            });

            $this->assertDatabaseHas('quotes', [
                'client_id' => $prospect->id,
                'subtotal' => 350.50,
            ]);

            $this->assertDatabaseHas('quote_line_items', [
                'description' => 'Item 2',
                'sort_order' => 0,
                'quantity' => 1.00,
            ]);
        });
    }
}
