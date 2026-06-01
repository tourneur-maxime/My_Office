<?php

namespace Tests\Unit\FacturX;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use App\Services\FacturX\FacturXService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacturXServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FacturXService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FacturXService(new \App\Services\LegalMentionService());
        
        // Ensure schemas are present for validation tests
        // They should be in storage/app/schemas/factur-x/
        // We assume they are set up by the previous steps
    }

    public function test_generate_xml_structure()
    {
        // Setup Models
        $user = User::factory()->create();
        $company = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Company',
            'siret' => '12345678901234',
            'vat_number' => 'FR12123456789',
            'address' => "10 Rue de la Paix\n75000 Paris",
            'is_vat_exempt' => false,
        ]);
        
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'company' => 'Client SAS',
            'address' => "20 Avenue du Client\n69000 Lyon",
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2025-001',
            'subtotal' => 100.00,
            'vat_amount' => 20.00,
            'total' => 120.00,
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Service Prestation',
            'quantity' => 1,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 120.00,
        ]);

        $invoice->load(['user.companyProfile', 'client', 'lineItems']);

        // Generate XML
        $xml = $this->service->generateXml($invoice);

        // Assertions
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('FAC-2025-001', $xml); // Invoice Number
        $this->assertStringContainsString('My Company', $xml); // Seller Name
        $this->assertStringContainsString('Client SAS', $xml); // Buyer Name
        $this->assertStringContainsString('120.00', $xml); // Total Amount
        $this->assertStringContainsString('Service Prestation', $xml); // Line Item
    }

    public function test_validate_xml_valid()
    {
        // We reuse the generation logic to get valid XML
        $user = User::factory()->create();
        $company = CompanyProfile::factory()->create(['user_id' => $user->id]);
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);
        
        $invoice->load(['user.companyProfile', 'client', 'lineItems']);
        
        $xml = $this->service->generateXml($invoice);

        // This assumes schemas are correctly installed in storage/app/schemas/factur-x/
        // If not, this test will fail, which is correct as it validates the setup too.
        try {
            $result = $this->service->validateXml($xml);
            $this->assertTrue($result['is_valid']);
        } catch (\Exception $e) {
            // Fail if validation throws exception
            $this->fail("XML Validation failed: " . $e->getMessage());
        }
    }

    public function test_validate_xml_invalid()
    {
        // Malformed XML
        $invalidXml = '<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100"><broken></rsm:CrossIndustryInvoice>';

        // The service should return result with is_valid = false, or throw exception if XML is totally broken
        try {
            $result = $this->service->validateXml($invalidXml);
            $this->assertFalse($result['is_valid']);
        } catch (\Exception $e) {
            // If it throws, that is also acceptable for invalid XML
            $this->assertTrue(true);
        }
    }
}
