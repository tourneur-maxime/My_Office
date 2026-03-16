<?php

namespace Tests\Unit\FacturX;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Prospect;
use App\Models\User;
use App\Services\FacturX\FacturXService;
use App\Services\LegalMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInstructionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_xml_contains_iban_and_due_date()
    {
        $user = User::factory()->create();
        $iban = 'FR7630006000011234567890189';
        $bic = 'ABCDEFGH';
        
        CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'name' => 'My Company',
            'iban' => $iban,
            'bic' => $bic,
            'default_payment_delay_days' => 15,
            'address' => "10 Rue de la Paix\n75000 Paris",
        ]);

        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'address' => "20 Avenue du Client\n69000 Lyon",
        ]);

        $issueDate = now()->format('Y-m-d');
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2025-001',
            'issue_date' => $issueDate,
            'due_date' => null, // Should be calculated
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'unit_price' => 100,
            'quantity' => 1,
            'vat_rate' => 20
        ]);

        $service = app(FacturXService::class);
        $xml = $service->generateXml($invoice);

        // Check IBAN (BT-84)
        $this->assertStringContainsString($iban, $xml);
        
        // Check BIC (BT-86)
        $this->assertStringContainsString($bic, $xml);

        // Check Due Date (BT-9)
        // 15 days after today
        $expectedDueDate = now()->addDays(15)->format('Ymd');
        $this->assertStringContainsString($expectedDueDate, $xml);
    }
}