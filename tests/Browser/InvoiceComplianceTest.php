<?php

namespace Tests\Browser;

use App\Models\CompanyProfile;
use App\Models\Prospect;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Throwable;

class InvoiceComplianceTest extends DuskTestCase
{
    /**
     * Test that compliance indicator shows success when data is valid.
     *
     * @throws Throwable
     */
    public function test_compliance_indicator_shows_success_for_valid_data(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $company = CompanyProfile::factory()->create([
                'user_id' => $user->id,
                'name' => 'My Company',
                'siret' => '12345678901234',
                'vat_number' => 'FR12123456789',
                'address' => "10 Rue de la Paix\n75000 Paris",
            ]);
            
            $client = Prospect::factory()->create([
                'user_id' => $user->id,
                'address' => "20 Avenue du Client\n69000 Lyon",
            ]);

            $browser->loginAs($user)
                ->visit(route('invoices.create'))
                ->assertSee('CONFORMITÉ FACTUR-X') // Panel title
                
                // Select client
                ->select('#client_id', $client->id)
                
                // Fill line item (already default, but let's be explicit)
                ->type('input[type="text"]', 'Service Test') // Description
                
                // Wait for debounce (1s) + API call
                ->pause(2000)
                
                ->assertSee('Conforme Factur-X')
                ->assertSee('Prêt pour signature');
        });
    }

    /**
     * Test that compliance indicator shows error when company data is missing.
     *
     * @throws Throwable
     */
    public function test_compliance_indicator_shows_error_for_missing_company_data(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            // Company without SIRET
            CompanyProfile::factory()->create([
                'user_id' => $user->id,
                'siret' => null, 
            ]);
            
            $client = Prospect::factory()->create(['user_id' => $user->id]);

            $browser->loginAs($user)
                ->visit(route('invoices.create'))
                ->select('#client_id', $client->id)
                ->type('input[type="text"]', 'Service Test')
                
                ->pause(2000)
                
                ->assertSee('Non Conforme')
                ->assertButtonDisabled('Créer la facture'); // Check if button is disabled
        });
    }
}
