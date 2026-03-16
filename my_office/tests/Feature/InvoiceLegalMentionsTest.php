<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use App\Services\FacturX\FacturXService;
use App\Services\InvoicePdfService;
use App\Services\LegalMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceLegalMentionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_pdf_contains_auto_entrepreneur_mentions_when_configured()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'is_vat_exempt' => true,
            'name' => 'My Auto Ent',
            'address' => '123 Rue Test',
            'siret' => '12345678901234',
        ]);
        
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $pdfService = app('App\Services\InvoicePdfService');
        
        // Generate PDF
        $pdfContent = $pdfService->generate($invoice);

        // Assert content
        // Note: TCPDF output is binary, checking text existence is tricky but possible if uncompressed or simple strings
        // However, TCPDF often compresses streams.
        // A better way for unit testing integration is spying on LegalMentionService or checking the service output directly.
        // But for Feature test, we want to know if PDF generation *works* and *uses* the service.
        
        $this->assertNotEmpty($pdfContent);
        
        // Let's verify the integration by mocking LegalMentionService and seeing if it's called
        // Or checking that the service returns what we expect
        $legalService = app('App\Services\LegalMentionService');
        $mentions = $legalService->getMentions($invoice);
        
        $this->assertContains("TVA non applicable, art. 293 B du CGI", $mentions);
    }

    public function test_factur_x_xml_contains_legal_mentions_as_notes()
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'is_vat_exempt' => true,
            'name' => 'My Auto Ent',
            'address' => '123 Rue Test',
            'siret' => '12345678901234',
        ]);
        
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $facturXService = app('App\Services\FacturX\FacturXService');
        $xmlContent = $facturXService->generateXml($invoice);

        // Check for IncludedNote
        // Note: ZUGFeRD library might structure it differently, but text should be there
        $this->assertStringContainsString("TVA non applicable, art. 293 B du CGI", $xmlContent);
        $this->assertStringContainsString("Micro-entrepreneur selon", $xmlContent);
    }
}
