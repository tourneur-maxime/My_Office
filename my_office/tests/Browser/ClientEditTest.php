<?php

namespace Tests\Browser;

use App\Models\Prospect;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ClientEditTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test that the client edit page pre-populates form fields.
     */
    public function test_client_edit_page_pre_populates_fields(): void
    {
        $this->browse(function (Browser $browser) {
            $user = \App\Models\User::factory()->create();
            $client = Prospect::factory()->create([
                'user_id' => $user->id,
                'status' => 'client',
                'name' => 'Test Client',
                'siret' => '12345678901234', // Example SIRET
                'vat_status' => true,
            ]);

            $browser->loginAs($user)
                ->visit(route('clients.edit', $client))
                ->dump() // Dump page source for debugging
                ->screenshot('client-edit-failure')
                ->assertValue('input[name="name"]', 'Test Client')
                ->assertValue('input[name="siret"]', '12345678901234')
                ->assertChecked('input[name="vat_status"]'); // Assuming it's a checkbox
        });
    }
}
