<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;
    protected \Mockery\MockInterface $invoiceNumberServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a mock for InvoiceNumberService
        $this->invoiceNumberServiceMock = \Mockery::mock(InvoiceNumberService::class);
        // Configure the mock to return a predictable invoice number
        $this->invoiceNumberServiceMock->shouldReceive('getNextInvoiceNumber')
                                       ->andReturnUsing(function ($user) {
                                           // Simulate a simple incrementing number for testing purposes
                                           static $counter = 0;
                                           $counter++;
                                           return "TEST-{$user->id}-".str_pad($counter, 4, '0', STR_PAD_LEFT);
                                       });

        $this->invoiceService = new InvoiceService($this->invoiceNumberServiceMock);
    }

    // Add tearDown for Mockery expectations
    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function test_it_calculates_invoice_totals_correctly_on_create(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);

        $lineItems = [
            [
                'description' => 'Item 1',
                'quantity' => 2,
                'unit_price' => 10.50,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Item 2',
                'quantity' => 1,
                'unit_price' => 100,
                'vat_rate' => 5.5,
            ]
        ];

        $invoice = $this->invoiceService->create($client, $lineItems);

        // Subtotal: (2 * 10.50) + (1 * 100) = 21 + 100 = 121
        // VAT: (21 * 0.20) + (100 * 0.055) = 4.2 + 5.5 = 9.7
        // Total: 121 + 9.7 = 130.7

        $this->assertEquals(121.00, $invoice->subtotal);
        $this->assertEquals(9.70, $invoice->vat_amount);
        $this->assertEquals(130.70, $invoice->total);
    }

    public function test_it_handles_rounding_consistently(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);

        $lineItems = [
            [
                'description' => 'Item with decimals',
                'quantity' => 1,
                'unit_price' => 33.33,
                'vat_rate' => 20,
            ]
        ];

        $invoice = $this->invoiceService->create($client, $lineItems);

        // Subtotal: 33.33
        // VAT: 33.33 * 0.2 = 6.666 -> rounded to 6.67
        // Total: 33.33 + 6.67 = 40.00

        $this->assertEquals(33.33, $invoice->subtotal);
        $this->assertEquals(6.67, $invoice->vat_amount);
        $this->assertEquals(40.00, $invoice->total);
    }

    public function test_it_updates_invoice_line_items_and_totals(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        
        $invoice = $this->invoiceService->create($client, [
            ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20]
        ]);

        $lineItem1 = $invoice->lineItems()->first();

        $updatedData = [
            [
                'id' => $lineItem1->id,
                'description' => 'Updated Item 1',
                'quantity' => 2,
                'unit_price' => 50,
                'vat_rate' => 10,
                'sort_order' => 1
            ],
            [
                'description' => 'New Item 2',
                'quantity' => 1,
                'unit_price' => 200,
                'vat_rate' => 20,
                'sort_order' => 0
            ]
        ];

        $this->invoiceService->update($invoice, $updatedData);

        $invoice->refresh();

        // Item 1: 2 * 50 = 100 HT, VAT 10% = 10 -> Total 110
        // Item 2: 1 * 200 = 200 HT, VAT 20% = 40 -> Total 240
        // Subtotal: 100 + 200 = 300
        // VAT: 10 + 40 = 50
        // Total: 350

        $this->assertEquals(300.00, $invoice->subtotal);
        $this->assertEquals(50.00, $invoice->vat_amount);
        $this->assertEquals(350.00, $invoice->total);
        
        $this->assertCount(2, $invoice->lineItems);
        $this->assertEquals('New Item 2', $invoice->lineItems()->orderBy('sort_order')->first()->description);
    }
}
