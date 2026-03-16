<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\User;
use App\Models\CompanyProfile;
use App\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class InvoiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->company = CompanyProfile::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Ma Super Entreprise',
            'address' => "123 Rue du Test\n75000 Paris",
            'siret' => '12345678901234',
        ]);
    }

    public function test_it_can_list_and_filter_invoices_by_client()
    {
        $client1 = Prospect::factory()->create(['user_id' => $this->user->id, 'name' => 'Client A']);
        $client2 = Prospect::factory()->create(['user_id' => $this->user->id, 'name' => 'Client B']);

        Invoice::factory()->create(['user_id' => $this->user->id, 'client_id' => $client1->id, 'invoice_number' => 'FAC-001']);
        Invoice::factory()->create(['user_id' => $this->user->id, 'client_id' => $client2->id, 'invoice_number' => 'FAC-002']);

        $response = $this->actingAs($this->user)->get(route('invoices.index', ['client_id' => $client1->id]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'FAC-001')
        );
    }

    public function test_it_can_filter_invoices_by_compliance_status()
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id, 
            'is_compliant' => true, 
            'invoice_number' => 'COMPLIANT-001'
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id, 
            'is_compliant' => false, 
            'invoice_number' => 'NON-COMPLIANT-001'
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.index', ['is_compliant' => 'true']));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'COMPLIANT-001')
        );
    }

    public function test_it_can_filter_invoices_by_date_range()
    {
        Invoice::factory()->create([
            'user_id' => $this->user->id, 
            'issue_date' => Carbon::parse('2026-01-01'),
            'invoice_number' => 'OLD-001'
        ]);
        Invoice::factory()->create([
            'user_id' => $this->user->id, 
            'issue_date' => Carbon::parse('2026-01-15'),
            'invoice_number' => 'NEW-001'
        ]);

        $response = $this->actingAs($this->user)->get(route('invoices.index', [
            'date_from' => '2026-01-10',
            'date_to' => '2026-01-20'
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'NEW-001')
        );
    }

    public function test_it_can_export_an_invoice_to_pdf_and_store_metadata()
    {
        Storage::fake('public');

        $client = Prospect::factory()->create(['user_id' => $this->user->id, 'name' => 'Client Export', 'address' => "Adresse\n75000 Ville"]);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id, 
            'client_id' => $client->id, 
            'invoice_number' => 'FAC-EXPORT',
            'status' => InvoiceStatus::Brouillon
        ]);
        
            // Add some line items
            \App\Models\InvoiceLineItem::factory()->create([
                'invoice_id' => $invoice->id,
                'description' => 'Service Test',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 20,
                'total' => 120, // (100 * 1) * 1.20 ? Or just 100 if HT. Looking at previous errors, it needs a value.
            ]);
        $response = $this->actingAs($this->user)->get(route('invoices.generate', $invoice->id));

        $response->assertHeader('Content-Type', 'application/pdf');
        
        // Verify it was stored
        $invoice->refresh();
        $this->assertNotNull($invoice->pdf_path);
        $this->assertTrue(Storage::disk('public')->exists($invoice->pdf_path));
        
        // Verify metadata
        $this->assertNotNull($invoice->facturx_metadata);
        $this->assertArrayHasKey('is_valid', $invoice->facturx_metadata);
        $this->assertArrayHasKey('validated_at', $invoice->facturx_metadata);
    }

    public function test_it_can_detect_numbering_gaps()
    {
        Invoice::factory()->create(['user_id' => $this->user->id, 'invoice_number' => 'FAC-2026-0001']);
        Invoice::factory()->create(['user_id' => $this->user->id, 'invoice_number' => 'FAC-2026-0003']);

        $gaps = Invoice::checkSequenceGaps($this->user->id);

        $this->assertEquals([2], $gaps);
    }
}
