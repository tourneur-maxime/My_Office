<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoiceEditUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_view_edit_invoice_form_for_draft_invoice(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);
        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($user)->get(route('invoices.edit', $invoice->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DocumentBuilder')
            ->has('invoice')
            ->has('invoice.line_items')
            ->where('invoice.id', $invoice->id)
        );
    }

    public function test_authenticated_user_cannot_edit_non_draft_invoice(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Envoyé']);

        $response = $this->actingAs($user)->get(route('invoices.edit', $invoice->id));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_invoice_line_items(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);
        
        $lineItem1 = InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Original Item',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 20,
            'total' => 120,
            'sort_order' => 0
        ]);

        $payload = [
            'line_items' => [
                [
                    'id' => $lineItem1->id,
                    'description' => 'Updated Item',
                    'quantity' => 2,
                    'unit_price' => 100,
                    'vat_rate' => 20,
                    'sort_order' => 1
                ],
                [
                    'description' => 'New Item',
                    'quantity' => 1,
                    'unit_price' => 50,
                    'vat_rate' => 10,
                    'sort_order' => 0
                ]
            ]
        ];

        $response = $this->actingAs($user)->put(route('invoices.update', $invoice->id), $payload);

        $response->assertRedirect(route('invoices.show', $invoice->id));
        $response->assertSessionHas('success', 'Facture mise à jour avec succès.');

        $this->assertDatabaseHas('invoice_line_items', [
            'id' => $lineItem1->id,
            'description' => 'Updated Item',
            'quantity' => 2,
            'total' => 240, // 2 * 100 * 1.2
            'sort_order' => 1
        ]);

        $this->assertDatabaseHas('invoice_line_items', [
            'description' => 'New Item',
            'total' => 55, // 1 * 50 * 1.1
            'sort_order' => 0
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'subtotal' => 250, // 200 + 50
            'vat_amount' => 45, // 40 + 5
            'total' => 295, // 250 + 45
        ]);
    }

    public function test_invoice_line_items_deletion_via_sync(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);
        
        $lineItem1 = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);
        $lineItem2 = InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $payload = [
            'line_items' => [
                [
                    'id' => $lineItem1->id,
                    'description' => $lineItem1->description,
                    'quantity' => $lineItem1->quantity,
                    'unit_price' => $lineItem1->unit_price,
                    'vat_rate' => $lineItem1->vat_rate,
                ]
            ]
        ];

        // We only send lineItem1, so lineItem2 should be deleted
        $response = $this->actingAs($user)->put(route('invoices.update', $invoice->id), $payload);

        $response->assertRedirect(route('invoices.show', $invoice->id));

        $this->assertDatabaseHas('invoice_line_items', ['id' => $lineItem1->id]);
        $this->assertDatabaseMissing('invoice_line_items', ['id' => $lineItem2->id]);
    }

    public function test_cannot_update_non_draft_invoice(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Envoyé']);

        $payload = ['line_items' => []];

        $response = $this->actingAs($user)->put(route('invoices.update', $invoice->id), $payload);

        $response->assertForbidden();
    }
}
