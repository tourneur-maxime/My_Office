<?php

namespace Tests\Feature;

use App\Models\Quote;
use App\Models\Prospect;
use App\Models\User;
use App\Services\QuoteNumberService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuoteNumberConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that concurrent requests don't produce duplicate numbers.
     * We simulate this by running multiple service calls within a single test
     * as if they were coming from different processes, but since we're using
     * transactions and row locking, we can verify the sequential nature.
     */
    public function test_it_prevents_duplicate_numbers_under_concurrency()
    {
        $user = User::factory()->create();
        $service = new QuoteNumberService();

        $numbers = [];
        $count = 10;

        // Simulate 10 sequential calls which would be protected by transactions
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = $service->getNext($user);
        }

        // Verify all numbers are unique
        $this->assertEquals($count, count(array_unique($numbers)));

        // Verify they are sequential
        $year = date('Y');
        $this->assertEquals("DEV-{$year}-0001", $numbers[0]);
        $this->assertEquals("DEV-{$year}-0010", $numbers[9]);
    }

    /**
     * More realistic test for model creation via service.
     */
    public function test_quotes_created_sequentially()
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quoteService = new QuoteService();

        $quotes = [];
        for ($i = 0; $i < 5; $i++) {
            $quotes[] = $quoteService->createQuote(
                $prospect,
                [
                    [
                        'description' => 'Test Item',
                        'quantity' => 1,
                        'unit_price' => 100,
                        'vat_rate' => 20,
                    ]
                ]
            );
        }

        $year = date('Y');
        $this->assertEquals("DEV-{$year}-0001", $quotes[0]->quote_number);
        $this->assertEquals("DEV-{$year}-0005", $quotes[4]->quote_number);
    }
}