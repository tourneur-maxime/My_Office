<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

// Added this line

class InvoiceShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_own_invoice()
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create(['user_id' => $user->id]); // Changed from Company to CompanyProfile
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        InvoiceLineItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->actingAs($user);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertStatus(200);

        $response->assertInertia(function (Assert $page) use ($invoice) {
            $page->component('Invoices/Show')
                ->has('invoice')
                ->where('invoice.id', $invoice->id)
                ->where('invoice.invoice_number', $invoice->invoice_number)
                ->has('invoice.client')
                ->has('invoice.line_items', 3);
        });
    }

    public function test_user_cannot_view_another_users_invoice()
    {
        $user1 = User::factory()->create();
        $client1 = Prospect::factory()->create(['user_id' => $user1->id]);
        $invoice1 = Invoice::factory()->create(['user_id' => $user1->id, 'client_id' => $client1->id]);

        $user2 = User::factory()->create();
        $this->actingAs($user2);

        $response = $this->get(route('invoices.show', $invoice1));

        $response->assertStatus(403);
    }

    public function test_user_can_download_their_own_invoice_as_pdf()
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'address' => "10 Rue de la Paix\n75000 Paris",
            'siret' => '12345678901234',
        ]);
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'address' => "20 Avenue du Client\n69000 Lyon",
            'zip_code' => '69000',
            'city' => 'Lyon',
        ]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($user);

        $response = $this->get(route('invoices.download', $invoice));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
    }
}
