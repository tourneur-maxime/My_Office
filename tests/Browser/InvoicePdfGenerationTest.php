<?php

namespace Tests\Browser;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Throwable;

class InvoicePdfGenerationTest extends DuskTestCase
{
    /**
     * Test that a user can trigger PDF/A-3 generation for an invoice.
     *
     * @throws Throwable
     */
    public function test_user_can_generate_factur_x_pdf(): void
    {
        $this->browse(function (Browser $browser) {
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
                'quantity' => 10,
                'unit_price' => 100.00,
                'vat_rate' => 20.00,
                'total' => 1200.00,
            ]);

            $browser->loginAs($user)
                ->visit(route('invoices.show', $invoice))
                ->waitFor('@generate-pdf-button')
                ->click('@generate-pdf-button')
                ->waitFor('@pdf-generation-message')
                ->assertSeeIn('@pdf-generation-message', 'La génération du PDF est en cours');
        });
    }

    /**
     * Test that PDF file is created after job processing.
     *
     * @throws Throwable
     */
    public function test_pdf_file_is_created_after_job_processing(): void
    {
        Storage::fake('local');

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
            'quantity' => 10,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 1200.00,
        ]);

        $this->browse(function (Browser $browser) use ($user, $invoice) {
            $browser->loginAs($user)
                ->visit(route('invoices.show', $invoice))
                ->waitFor('@generate-pdf-button')
                ->click('@generate-pdf-button')
                ->waitFor('@pdf-generation-message');
        });

        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $year = $invoice->created_at->format('Y');
        $month = $invoice->created_at->format('m');
        $expected_path = "invoices/{$year}/{$month}/facture-{$invoice->invoice_number}.pdf";

        Storage::disk('local')->assertExists($expected_path);
    }

    /**
     * Test that the generated PDF has valid magic number (PDF signature).
     *
     * @throws Throwable
     */
    public function test_generated_pdf_has_valid_magic_number(): void
    {
        Storage::fake('local');

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

        $this->browse(function (Browser $browser) use ($user, $invoice) {
            $browser->loginAs($user)
                ->visit(route('invoices.show', $invoice))
                ->waitFor('@generate-pdf-button')
                ->click('@generate-pdf-button')
                ->waitFor('@pdf-generation-message');
        });

        $this->artisan('queue:work', ['--stop-when-empty' => true]);

        $year = $invoice->created_at->format('Y');
        $month = $invoice->created_at->format('m');
        $file_path = "invoices/{$year}/{$month}/facture-{$invoice->invoice_number}.pdf";

        $content = Storage::disk('local')->get($file_path);

        $this->assertStringStartsWith('%PDF-', $content, 'Le fichier généré doit être un PDF valide');
    }
}
