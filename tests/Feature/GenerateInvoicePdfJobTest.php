<?php

namespace Tests\Feature;

use App\Jobs\GenerateInvoicePdfJob;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateInvoicePdfJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_job_is_dispatched_when_generate_pdf_endpoint_is_called(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
        ]);

        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $response = $this->actingAs($user)
            ->postJson(route('invoices.generatePdf', $invoice));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'La génération du PDF est en cours. Vous serez notifié une fois terminée.',
            ]);

        Queue::assertPushed(GenerateInvoicePdfJob::class, function ($job) use ($invoice) {
            return $job->invoice->id === $invoice->id;
        });
    }

    public function test_job_saves_pdf_to_correct_storage_path(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Service de développement',
            'quantity' => 5,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 600.00,
        ]);

        $job = new GenerateInvoicePdfJob($invoice);
        $job->handle(
            app(\App\Services\FacturX\ComplianceEngine::class),
            app(\App\Services\FacturX\PdfGeneratorService::class)
        );

        $year = $invoice->created_at->format('Y');
        $month = $invoice->created_at->format('m');
        $expected_path = "invoices/{$year}/{$month}/facture-{$invoice->invoice_number}.pdf";

        Storage::disk('local')->assertExists($expected_path);
    }

    public function test_generated_pdf_has_valid_pdf_magic_number(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Service de test',
            'quantity' => 1,
            'unit_price' => 50.00,
            'vat_rate' => 20.00,
            'total' => 60.00,
        ]);

        $job = new GenerateInvoicePdfJob($invoice);
        $job->handle(
            app(\App\Services\FacturX\ComplianceEngine::class),
            app(\App\Services\FacturX\PdfGeneratorService::class)
        );

        $year = $invoice->created_at->format('Y');
        $month = $invoice->created_at->format('m');
        $file_path = "invoices/{$year}/{$month}/facture-{$invoice->invoice_number}.pdf";

        $content = Storage::disk('local')->get($file_path);

        $this->assertStringStartsWith('%PDF-', $content, 'Le fichier généré doit être un PDF valide');
    }

    public function test_unauthorized_user_cannot_generate_pdf_for_other_users_invoice(): void
    {
        $owner = User::factory()->create();
        $other_user = User::factory()->create();

        CompanyProfile::factory()->create(['user_id' => $owner->id]);

        $prospect = Prospect::factory()->create(['user_id' => $owner->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $owner->id,
            'client_id' => $prospect->id,
        ]);

        $response = $this->actingAs($other_user)
            ->postJson(route('invoices.generatePdf', $invoice));

        $response->assertForbidden();
    }

    public function test_job_is_dispatched_on_invoices_queue(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
        ]);

        InvoiceLineItem::factory()->create(['invoice_id' => $invoice->id]);

        $this->actingAs($user)
            ->postJson(route('invoices.generatePdf', $invoice));

        Queue::assertPushedOn('invoices', GenerateInvoicePdfJob::class);
    }

    public function test_compliance_engine_generates_pdf_with_xmp_metadata(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);

        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'Service Factur-X',
            'quantity' => 1,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 120.00,
        ]);

        $pdf_generator = app(\App\Services\FacturX\PdfGeneratorService::class);

        $xml_content = '<?xml version="1.0" encoding="UTF-8"?><test>content</test>';
        $conformance_level = 'EN 16931';

        $pdf_content = $pdf_generator->generateWithFacturX($invoice, $xml_content, $conformance_level);

        $this->assertStringStartsWith('%PDF-', $pdf_content, 'Le fichier généré doit être un PDF valide');
        $this->assertStringContainsString('fx:ConformanceLevel', $pdf_content, 'Le PDF doit contenir les métadonnées XMP Factur-X');
    }
}
