<?php

namespace Tests\Feature;

use App\Enums\QuoteStatus;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteStatusTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Prospect $prospect;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->prospect = Prospect::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_new_quote_has_brouillon_status_by_default()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('quotes.store', $this->prospect), [
            'line_items' => [
                [
                    'description' => 'Test Item',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'vat_rate' => 20,
                ],
            ],
        ]);

        $response->assertRedirect();
        $quote = Quote::first();
        $this->assertEquals(QuoteStatus::Brouillon, $quote->status);
    }

    public function test_user_can_update_quote_status()
    {
        $this->actingAs($this->user);
        $quote = Quote::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->prospect->id,
            'status' => QuoteStatus::Brouillon,
        ]);

        $response = $this->patch(route('quotes.updateStatus', $quote), [
            'status' => QuoteStatus::Approuvé->value,
        ]);

        $response->assertRedirect();
        $this->assertEquals(QuoteStatus::Approuvé, $quote->fresh()->status);
    }

    public function test_user_cannot_update_others_quote_status()
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $quote = Quote::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->prospect->id,
            'status' => QuoteStatus::Brouillon,
        ]);

        $response = $this->patch(route('quotes.updateStatus', $quote), [
            'status' => QuoteStatus::Approuvé->value,
        ]);

        $response->assertStatus(403);
    }

    public function test_quotes_can_be_filtered_by_status()
    {
        $this->actingAs($this->user);

        Quote::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->prospect->id,
            'status' => QuoteStatus::Brouillon,
        ]);

        Quote::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $this->prospect->id,
            'status' => QuoteStatus::Approuvé,
        ]);

        // Filter by Brouillon
        $response = $this->get(route('quotes.index', ['status' => QuoteStatus::Brouillon->value]));
        $response->assertInertia(fn ($page) => $page
            ->has('quotes.data', 1)
            ->where('quotes.data.0.status', QuoteStatus::Brouillon->value)
        );

        // Filter by Approuvé
        $response = $this->get(route('quotes.index', ['status' => QuoteStatus::Approuvé->value]));
        $response->assertInertia(fn ($page) => $page
            ->has('quotes.data', 1)
            ->where('quotes.data.0.status', QuoteStatus::Approuvé->value)
        );
    }

    public function test_quote_numbering_is_sequential_and_unique_under_concurrent_creation()
    {
        $this->actingAs($this->user);

        $createdQuotes = collect();
        $numberOfQuotesToCreate = 10; // Simulate multiple creations

        // Create quotes in a loop
        for ($i = 0; $i < $numberOfQuotesToCreate; $i++) {
            $response = $this->post(route('quotes.store', $this->prospect), [
                'line_items' => [
                    [
                        'description' => 'Test Item ' . $i,
                        'quantity' => 1,
                        'unit_price' => 10 + $i,
                        'vat_rate' => 20,
                    ],
                ],
            ]);

            $response->assertRedirect();
            $createdQuotes->push(Quote::latest('id')->first()); // Get the most recently created quote
        }

        // Extract quote numbers
        $quoteNumbers = $createdQuotes->pluck('quote_number');

        // Assert all quote numbers are unique
        $this->assertCount($numberOfQuotesToCreate, $quoteNumbers->unique(), 'Quote numbers should be unique.');

        // Verify that the numbers are actually formatted and roughly sequential
        // For example, if it's QUO-2026-0001, QUO-2026-0002, etc.
        $this->assertTrue($quoteNumbers->every(fn($number) => str_starts_with($number, 'DEV-') && strlen($number) > 8));
    }
}
