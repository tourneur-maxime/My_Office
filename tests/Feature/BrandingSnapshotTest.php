<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Template;
use App\Models\User;
use App\Models\CompanyProfile;
use App\Models\Prospect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_captures_branding_snapshot_on_generation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'primary_color' => '#111111',
            'font_family' => 'serif',
        ]);

        $client = Prospect::factory()->create(['user_id' => $user->id]);
        
        $template = Template::factory()->create([
            'user_id' => $user->id,
            'primary_color' => '#222222',
            'font_family' => 'sans-serif',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'template_id' => $template->id,
            'invoice_number' => 'FAC-TEST-001',
        ]);

        // Mock FacturX and PDF generation
        $this->partialMock(\App\Services\FacturX\FacturXService::class, function ($mock) {
            $mock->shouldReceive('generateXml')->andReturn('<xml></xml>');
            $mock->shouldReceive('validateXml')->andReturn(['is_valid' => true]);
        });

        // Trigger generation
        $this->get(route('invoices.generate', $invoice->id));

        $invoice->refresh();

        // Check snapshot captured template branding
        $this->assertNotNull($invoice->branding_snapshot);
        $this->assertEquals('#222222', $invoice->branding_snapshot['primary_color']);
        $this->assertEquals('sans-serif', $invoice->branding_snapshot['font_family']);

        // Modify template and check snapshot remains unchanged
        $template->update(['primary_color' => '#FF0000']);
        
        $this->assertEquals('#222222', $invoice->branding_snapshot['primary_color']);
    }

    public function test_invoice_pdf_service_uses_snapshot_if_present()
    {
        $user = User::factory()->create();
        $companyProfile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'primary_color' => '#111111',
        ]);

        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'branding_snapshot' => [
                'primary_color' => '#999999',
                'logo_size' => 123,
                'logo_position' => 'center',
                'font_family' => 'monospace'
            ],
        ]);

        $templateService = new \App\Services\TemplateService();
        $branding = $templateService->resolveBranding($invoice);

        $this->assertEquals('#999999', $branding->primaryColor);
        $this->assertEquals(123, $branding->logoSize);
        $this->assertEquals('monospace', $branding->fontFamily);
    }
}
