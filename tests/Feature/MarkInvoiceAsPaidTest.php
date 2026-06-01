<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class MarkInvoiceAsPaidTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_their_own_invoice_as_paid()
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);

        $this->actingAs($user);

        $response = $this->patch(route('invoices.markAsPaid', $invoice));

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'Payé',
        ]);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_user_cannot_mark_another_users_invoice_as_paid()
    {
        $user1 = User::factory()->create();
        $client1 = Prospect::factory()->create(['user_id' => $user1->id]);
        $invoice1 = Invoice::factory()->create(['user_id' => $user1->id, 'client_id' => $client1->id, 'status' => 'Brouillon']);

        $user2 = User::factory()->create();
        $this->actingAs($user2);

        $response = $this->patch(route('invoices.markAsPaid', $invoice1));

        $response->assertStatus(403);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice1->id,
            'status' => 'Brouillon',
        ]);
    }

    public function test_activity_is_logged_when_invoice_is_marked_as_paid()
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);

        $this->actingAs($user);

        $this->patch(route('invoices.markAsPaid', $invoice));

        $this->assertCount(2, Activity::all());
        $activity = Activity::all()->last();
        $this->assertEquals($invoice->id, $activity->subject_id);
        $this->assertEquals('updated', $activity->description);
    }
}
