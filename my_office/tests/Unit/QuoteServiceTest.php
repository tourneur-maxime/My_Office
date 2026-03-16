<?php

namespace Tests\Unit;

use App\Enums\QuoteStatus;
use App\Models\Prospect;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test; // Import the QuoteStatus enum
use Tests\TestCase; // Import Test attribute

class QuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuoteService $quoteService;

    protected User $user;

    protected Prospect $prospect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quoteService = new QuoteService;
        $this->user = User::factory()->create();
        $this->prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
    }

    /** @test */
    public function it_calculates_quote_totals_correctly(): void
    {
        $lineItemsData = [
            [
                'description' => 'Service A',
                'quantity' => 2,
                'unit_price' => 100.00,
                'vat_rate' => 20.00, // 20%
            ],
            [
                'description' => 'Service B',
                'quantity' => 1,
                'unit_price' => 50.00,
                'vat_rate' => 10.00, // 10%
            ],
        ];

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData);

        // Expected Calculations:
        // Service A: quantity=2, unit_price=100, vat_rate=20%
        //   Excl VAT: 2 * 100 = 200
        //   VAT: 200 * 0.20 = 40
        //   Incl VAT: 240
        // Service B: quantity=1, unit_price=50, vat_rate=10%
        //   Excl VAT: 1 * 50 = 50
        //   VAT: 50 * 0.10 = 5
        //   Incl VAT: 55
        //
        // Total Quote:
        //   Subtotal (Excl VAT): 200 + 50 = 250
        //   VAT Amount: 40 + 5 = 45
        //   Grand Total (Incl VAT): 250 + 45 = 295

        $this->assertEqualsWithDelta(250.00, $quote->subtotal, 0.001);
        $this->assertEqualsWithDelta(45.00, $quote->vat_amount, 0.001);
        $this->assertEqualsWithDelta(295.00, $quote->total, 0.001);

        $this->assertCount(2, $quote->lineItems);
        $this->assertEqualsWithDelta(240.00, $quote->lineItems[0]->total, 0.001);
        $this->assertEqualsWithDelta(55.00, $quote->lineItems[1]->total, 0.001);
    }

    /** @test */
    public function it_handles_zero_vat_rate_correctly(): void
    {
        $lineItemsData = [
            [
                'description' => 'Service C',
                'quantity' => 5,
                'unit_price' => 10.00,
                'vat_rate' => 0.00, // 0% VAT
            ],
        ];

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData);

        $this->assertEqualsWithDelta(50.00, $quote->subtotal, 0.001);
        $this->assertEqualsWithDelta(0.00, $quote->vat_amount, 0.001);
        $this->assertEqualsWithDelta(50.00, $quote->total, 0.001);
        $this->assertEqualsWithDelta(50.00, $quote->lineItems[0]->total, 0.001);
    }

    /** @test */
    public function it_handles_multiple_decimal_places_in_prices_and_vat(): void
    {
        $lineItemsData = [
            [
                'description' => 'Service D',
                'quantity' => 3,
                'unit_price' => 12.345,
                'vat_rate' => 19.6, // Real-world VAT rate example
            ],
        ];

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData);

        // Expected Calculations (Rounded to 2 decimals as per QuoteService):
        // Excl VAT: 3 * 12.345 = 37.035 -> rounded to 37.04
        // VAT: 37.04 * 0.196 = 7.25984 -> rounded to 7.26
        // Total: 37.04 + 7.26 = 44.30

        $this->assertEqualsWithDelta(37.04, $quote->subtotal, 0.001);
        $this->assertEqualsWithDelta(7.26, $quote->vat_amount, 0.001);
        $this->assertEqualsWithDelta(44.30, $quote->total, 0.001);
        // Note: QuoteLineItem model also has a boot hook that calculates its own total
        // but we verify the service's aggregated results here.
    }

    /** @test */
    public function it_sets_expires_at_date_correctly(): void
    {
        $lineItemsData = [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 20],
        ];
        $expiresAt = now()->addDays(7);

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData, $expiresAt);

        $this->assertNotNull($quote->expires_at);
        $this->assertEquals($expiresAt->format('Y-m-d'), $quote->expires_at->format('Y-m-d'));
    }

    /** @test */
    public function it_persists_template_id_when_creating_quote(): void
    {
        $lineItemsData = [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 20],
        ];
        $template = \App\Models\Template::factory()->create(['user_id' => $this->user->id]);

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData, null, $template->id);

        $this->assertNotNull($quote->template_id);
        $this->assertEquals($template->id, $quote->template_id);
    }

    /** @test */
    public function it_sets_initial_status_to_brouillon(): void
    {
        $lineItemsData = [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 10, 'vat_rate' => 20],
        ];

        $quote = $this->quoteService->createQuote($this->prospect, $lineItemsData);

        $this->assertEquals(QuoteStatus::Brouillon, $quote->status);
    }
}
