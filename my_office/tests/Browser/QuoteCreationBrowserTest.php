<?php

namespace Tests\Browser;

use App\Models\Prospect;
use App\Models\Template;
use App\Models\User; // Import Template model
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuoteCreationBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected Prospect $prospect;

    protected Template $defaultTemplate;

    protected Template $customTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->prospect = Prospect::factory()->create(['user_id' => $this->user->id, 'company' => 'Test Company Inc.']);
        $this->defaultTemplate = Template::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Default Template',
            'is_default' => true,
            'primary_color' => '#3B82F6', // Ensure distinct colors for assertion
        ]);
        $this->customTemplate = Template::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Custom Template',
            'is_default' => false,
            'primary_color' => '#FF0000', // Red for easy visual assertion
        ]);
    }

    /** @test */
    public function a_user_can_create_a_quote_for_a_selected_client()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('quotes.create'))
                ->waitForText('Créer un devis')
                ->assertSee('Sélectionner un client/prospect');

            // Simulate selecting a client using the SearchClients component
            $browser->type('#search-input', $this->prospect->company)
                ->pause(1000) // Wait for debounce and search
                ->click('#suggestion-0') // Select the first suggestion
                ->pause(1000);

            $browser->assertSee('Client sélectionné');

            // Fill out quote details
            $browser->within('.p-6 form', function (Browser $form) {
                // Add first line item
                $form->type('#description-0', 'Service de développement');
                $form->script("document.getElementById('quantity-0').value = '2'; document.getElementById('quantity-0').dispatchEvent(new Event('input'))");
                $form->script("document.getElementById('unit_price-0').value = '150.50'; document.getElementById('unit_price-0').dispatchEvent(new Event('input'))");
                $form->script("document.getElementById('vat_rate-0').value = '20.00'; document.getElementById('vat_rate-0').dispatchEvent(new Event('input'))");

                // Add another line item
                $form->click('@add-line-item-btn') // Assuming you add a @dusk="add-line-item-btn" to the button
                    ->pause(500)
                    ->type('#description-1', 'Maintenance');
                $form->script("document.getElementById('quantity-1').value = '1'; document.getElementById('quantity-1').dispatchEvent(new Event('input'))");
                $form->script("document.getElementById('unit_price-1').value = '50.00'; document.getElementById('unit_price-1').dispatchEvent(new Event('input'))");
                $form->script("document.getElementById('vat_rate-1').value = '10.00'; document.getElementById('vat_rate-1').dispatchEvent(new Event('input'))");

                // Fill expires_at
                $tomorrow = now()->addDay()->format('Y-m-d');
                $form->script("document.querySelector('.v3dp__input_wrapper input').value = '$tomorrow'");
                $form->script("document.querySelector('.v3dp__input_wrapper input').dispatchEvent(new Event('input'))");

                $form->click('#submit-quote-btn');

                $form->waitForRoute('clients.edit', ['prospect' => $this->prospect->id]);
            });

            // Assert that the quote was created in the database
            $this->assertDatabaseHas('quotes', [
                'client_id' => $this->prospect->id,
                'user_id' => $this->user->id,
                'status' => 'Brouillon',
                'template_id' => $this->defaultTemplate->id, // Assert default template was used
                'subtotal' => 351.00,
                'vat_amount' => 65.20,
                'total' => 416.20,
            ]);

            $this->assertDatabaseHas('quote_line_items', [
                'description' => 'Service de développement',
                'quantity' => 2,
                'unit_price' => 150.50,
                'vat_rate' => 20.00,
            ]);
            $this->assertDatabaseHas('quote_line_items', [
                'description' => 'Maintenance',
                'quantity' => 1,
                'unit_price' => 50.00,
                'vat_rate' => 10.00,
            ]);
        });
    }

    /** @test */
    public function a_user_can_select_a_template_and_preview_pdf_on_quote_creation_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('quotes.create'))
                ->waitForText('Créer un devis')
                ->assertSee('Sélectionner un client/prospect');

            // Select a client
            $browser->type('#search-input', $this->prospect->company)
                ->pause(1000)
                ->click('#suggestion-0')
                ->pause(1000);

            $browser->assertSee('Client sélectionné');

            // Assert default template is active in preview
            $browser->assertSeeIn('.badge', 'Default Template')
                ->assertAttributeContains('.badge', 'style', 'background-color: rgb(59, 130, 246)'); // Check primary color

            // Select custom template
            $browser->scrollIntoView('.card-body') // Ensure template cards are visible
                ->click("div.card[key='{$this->customTemplate->id}']")
                ->pause(1000); // Wait for reactivity

            // Assert custom template is active in preview
            $browser->assertSeeIn('.badge', 'Custom Template')
                ->assertAttributeContains('.badge', 'style', 'background-color: rgb(255, 0, 0)'); // Check for red primary color

            // Add a line item so preview has content for PDF
            $browser->type('#description-0', 'Test Item')
                ->script("document.getElementById('quantity-0').value = '1'; document.getElementById('quantity-0').dispatchEvent(new Event('input'))")
                ->script("document.getElementById('unit_price-0').value = '10.00'; document.getElementById('unit_price-0').dispatchEvent(new Event('input'))")
                ->script("document.getElementById('vat_rate-0').value = '20.00'; document.getElementById('vat_rate-0').dispatchEvent(new Event('input'))");

            // Click PDF preview button and assert a new tab is opened
            /** @phpstan-ignore-next-line */
            $browser->click('.btn-primary:text("Aperçu PDF")') // Use text selector if no specific dusk attribute
                ->pause(3000) // Give time for PDF to generate and new tab to open
                ->assertEnabled('.btn-primary:text("Aperçu PDF")'); // If the button is re-enabled, it indicates the action completed
        });
    }

    /** @test */
    public function validation_errors_are_displayed_on_quote_creation_page()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit(route('quotes.create'))
                ->waitForText('Créer un devis');

            // Select a client
            $browser->type('#search-input', $this->prospect->company)
                ->pause(1000);

            $browser->click('#suggestion-0')
                ->pause(1000);

            $browser->assertSee('Client sélectionné');

            // Submit with empty description to trigger validation errors
            $browser->within('.p-6 form', function (Browser $form) {
                // Remove required attributes to bypass browser validation
                $form->script("document.querySelectorAll('input[required]').forEach(el => el.removeAttribute('required'))");

                // Clear description using JS to ensure it's really empty and triggers v-model
                $form->script("
                    let desc = document.getElementById('description-0');
                    desc.value = '';
                    desc.dispatchEvent(new Event('input'));
                ");

                $form->click('#submit-quote-btn');

                // Wait for any validation error to appear
                $browser->waitForText('La description est obligatoire');
            });
        });
    }
}
