<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFacturXTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_returns_pdf()
    {
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

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2025-001',
        ]);

        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($user)
            ->post(route('invoices.generate', $invoice));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_download_invoice_returns_pdf()
    {
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

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2025-002',
        ]);

        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($user)
            ->get(route('invoices.download', $invoice));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
