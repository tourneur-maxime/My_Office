<?php

namespace Tests\Browser;

use App\Models\Prospect;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Throwable;

class ProspectDeleteTest extends DuskTestCase
{
    /**
     * Test a user can delete their prospect.
     *
     * @throws Throwable
     */
    public function test_user_can_delete_their_prospect(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $prospect = Prospect::factory()->create(['user_id' => $user->id]);

            $browser->loginAs($user)
                ->visit('/clients')
                ->waitForText($prospect->name)
                ->press('Supprimer') // Click the delete button
                ->waitForText('Supprimer le prospect '.$prospect->name.'?') // Wait for the confirmation modal
                ->press('Supprimer') // Confirm deletion within the modal
                ->waitForRoute('clients.index')
                ->assertSee('Prospect deleted successfully.');

            $this->assertDatabaseMissing('prospects', ['id' => $prospect->id]);
        });
    }

    /**
     * Test a user cannot delete a prospect with associated notes.
     *
     * @throws Throwable
     */
    public function test_user_cannot_delete_prospect_with_notes(): void
    {
        $this->browse(function (Browser $browser) {
            $user = User::factory()->create();
            $prospect = Prospect::factory()->create(['user_id' => $user->id]);
            $prospect->notes()->create(['user_id' => $user->id, 'content' => 'Some note']);

            $browser->loginAs($user)
                ->visit('/clients')
                ->waitForText($prospect->name)
                ->press('Supprimer') // Click the delete button
                ->waitForText('Supprimer le prospect '.$prospect->name.'?') // Wait for the confirmation modal
                ->assertSee('Si des devis ou factures sont liés, la suppression sera bloquée.') // Assert warning message
                ->press('Supprimer') // Confirm deletion within the modal
                ->waitForRoute('clients.index')
                ->assertSee('Cannot delete prospect with associated notes. Please delete notes first.');

            $this->assertDatabaseHas('prospects', ['id' => $prospect->id]);
            $this->assertDatabaseHas('notes', ['prospect_id' => $prospect->id]);
        });
    }
}
