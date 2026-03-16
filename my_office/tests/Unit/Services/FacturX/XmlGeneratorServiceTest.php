<?php

namespace Tests\Unit\Services\FacturX;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use App\Services\FacturX\XmlGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XmlGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_valid_cii_xml_from_an_invoice()
    {
        $user = User::factory()->create();
        $company = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'zip_code' => '75001',
            'city' => 'Paris',
        ]);
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2023-0001',
            'created_at' => '2023-01-15 10:00:00',
            'due_date' => '2023-02-15',
            'subtotal' => 200,
            'vat_amount' => 40,
            'total' => 240,
        ]);
        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Product 1',
            'quantity' => 1,
            'unit_price' => 100,
            'vat_rate' => 20,
            'total' => 120,
        ]);
        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Product 2',
            'quantity' => 2,
            'unit_price' => 50,
            'vat_rate' => 20,
            'total' => 120,
        ]);

        $service = new XmlGeneratorService;
        $xmlString = $service->generate($invoice);

        // Assert that the XML is well-formed
        $dom = new \DOMDocument;
        $dom->loadXML($xmlString);
        // $this->assertTrue($dom->schemaValidateSource($xmlString)); // Basic validation, a proper XSD validation would be better if available

        // Assert key invoice data is present in the XML string
        $this->assertStringContainsString('<ram:ID>'.$invoice->invoice_number.'</ram:ID>', $xmlString);
        $this->assertStringContainsString('<ram:Name>'.$company->name.'</ram:Name>', $xmlString); // Seller name from factory
        $this->assertStringContainsString('<ram:Name>'.$client->name.'</ram:Name>', $xmlString); // Buyer name from factory
        $this->assertStringContainsString('<ram:GrandTotalAmount>'.number_format($invoice->total, 2, '.', '').'</ram:GrandTotalAmount>', $xmlString);
        $this->assertStringContainsString('<ram:LineID>1</ram:LineID>', $xmlString);
        $this->assertStringContainsString('<ram:LineID>2</ram:LineID>', $xmlString);
    }

    // Remove the dd($xmlString) call and the fixture file.
}
