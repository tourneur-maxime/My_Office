<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Jobs\GenerateQuotePdfJob;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_view_quote_creation_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('quotes.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DocumentBuilder')
        );
    }

    /*
    public function test_authenticated_user_cannot_view_quote_creation_form_for_another_users_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)->get(route('quotes.create', $prospect->id));

        $response->assertForbidden();
    }
    */

    public function test_authenticated_user_can_create_a_quote_with_line_items_for_their_client(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $lineItems = [
            [
                'description' => 'Service A',
                'quantity' => 2,
                'unit_price' => 100.00,
                'vat_rate' => 20.00,
            ],
            [
                'description' => 'Service B',
                'quantity' => 1,
                'unit_price' => 50.00,
                'vat_rate' => 10.00,
            ],
        ];

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect(route('quotes.index'));
        $response->assertSessionHas('success', 'Devis créé avec succès.');

        $this->assertDatabaseHas('quotes', [
            'client_id' => $prospect->id,
            'user_id' => $user->id,
            'status' => QuoteStatus::Brouillon,
            'subtotal' => 250.00, // (2*100) + (1*50)
            'vat_amount' => 45.00, // (200*0.20) + (50*0.10) = 40 + 5 = 45
            'total' => 295.00, // 250 + 45
        ]);

        $quote = Quote::where('client_id', $prospect->id)->first();

        $this->assertDatabaseHas('quote_line_items', [
            'quote_id' => $quote->id,
            'description' => 'Service A',
            'quantity' => 2,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 240.00, // 2 * 100 * (1 + 0.20)
        ]);
        $this->assertDatabaseHas('quote_line_items', [
            'quote_id' => $quote->id,
            'description' => 'Service B',
            'quantity' => 1,
            'unit_price' => 50.00,
            'vat_rate' => 10.00,
            'total' => 55.00, // 1 * 50 * (1 + 0.10)
        ]);
    }

    public function test_authenticated_user_cannot_create_a_quote_for_another_users_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);

        $lineItems = [
            [
                'description' => 'Service X',
                'quantity' => 1,
                'unit_price' => 100.00,
                'vat_rate' => 20.00,
            ],
        ];

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('quotes', ['client_id' => $prospect->id]);
    }

    public function test_quote_creation_requires_line_items(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => [], // Empty line items
        ]);

        $response->assertSessionHasErrors('line_items');
        $response->assertStatus(302);
    }

    public function test_quote_line_item_validation_rules(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => [
                [
                    'description' => '', // Missing description
                    'quantity' => 0,     // Invalid quantity
                    'unit_price' => -10, // Invalid unit price
                    'vat_rate' => 101,   // Invalid VAT rate
                ],
            ],
        ]);

        $response->assertSessionHasErrors([
            'line_items.0.description',
            'line_items.0.quantity',
            'line_items.0.unit_price',
            'line_items.0.vat_rate',
        ]);
        $response->assertStatus(302);
    }

    public function test_quote_number_is_automatically_assigned_and_unique(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $this->actingAs($user)->post(route('quotes.store', $prospect->id), ['line_items' => $lineItems]);
        $this->actingAs($user)->post(route('quotes.store', $prospect->id), ['line_items' => $lineItems]);

        $quotes = Quote::where('user_id', $user->id)->get();
        $this->assertCount(2, $quotes);
        $this->assertNotNull($quotes[0]->quote_number);
        $this->assertNotNull($quotes[1]->quote_number);
        $this->assertNotEquals($quotes[0]->quote_number, $quotes[1]->quote_number);

        // Assert format based on default 'DEV-{YEAR}-{SEQUENCE}'
        $year = date('Y');
        $this->assertStringStartsWith("DEV-{$year}-", $quotes[0]->quote_number);
    }

    public function test_expires_at_field_is_saved_correctly(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $expiresAt = now()->addDays(7)->format('Y-m-d');

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => $lineItems,
            'expires_at' => $expiresAt,
        ]);

        $response->assertRedirect();

        $quote = Quote::where('client_id', $prospect->id)->first();
        $this->assertNotNull($quote);
        $this->assertStringContainsString($expiresAt, $quote->expires_at);
    }

    public function test_expires_at_validation_must_be_a_future_date(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $pastDate = now()->subDays(1)->format('Y-m-d');

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => $lineItems,
            'expires_at' => $pastDate,
        ]);

        $response->assertSessionHasErrors('expires_at');
        $response->assertStatus(302);
    }

    public function test_quote_number_format_is_customizable(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        // Create custom settings
        \App\Models\QuoteNumberSetting::create([
            'user_id' => $user->id,
            'prefix' => 'CUST',
            'include_year' => true,
            'digit_count' => 4,
            'last_number' => 0
        ]);

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->post(route('quotes.store', $prospect->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect();
        $quote = Quote::where('client_id', $prospect->id)->first();
        $this->assertNotNull($quote->quote_number);

        $year = date('Y');
        $this->assertStringStartsWith("CUST-{$year}-", $quote->quote_number);
    }

    public function test_authenticated_user_can_update_their_quote_status(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $response = $this->actingAs($user)->patch(route('quotes.updateStatus', $quote->id), [
            'status' => QuoteStatus::Envoyé->value,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Statut du devis mis à jour avec succès.');
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => QuoteStatus::Envoyé]);
    }

    public function test_authenticated_user_cannot_update_another_users_quote_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);
        $quote = Quote::factory()->create(['user_id' => $otherUser->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $response = $this->actingAs($user)->patch(route('quotes.updateStatus', $quote->id), [
            'status' => QuoteStatus::Envoyé->value,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => QuoteStatus::Brouillon]); // Status should not change
    }

    public function test_quote_status_update_requires_valid_status(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $response = $this->actingAs($user)->patch(route('quotes.updateStatus', $quote->id), [
            'status' => 'InvalidStatus',
        ]);

        $response->assertSessionHasErrors('status');
        $response->assertStatus(302);
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => 'Brouillon']); // Status should not change
    }

    public function test_authenticated_user_can_view_their_quotes_list(): void
    {
        $user = User::factory()->create();
        Quote::factory()->count(3)->create(['user_id' => $user->id, 'client_id' => Prospect::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->actingAs($user)->get(route('quotes.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 3)
            ->has('filters')
            ->has('quoteStatuses')
            ->has('prospects')
        );
    }

    public function test_quotes_list_can_be_filtered_by_client(): void
    {
        $user = User::factory()->create();
        $prospect1 = Prospect::factory()->create(['user_id' => $user->id]);
        $prospect2 = Prospect::factory()->create(['user_id' => $user->id]);

        Quote::factory()->count(2)->create(['user_id' => $user->id, 'client_id' => $prospect1->id]);
        Quote::factory()->count(1)->create(['user_id' => $user->id, 'client_id' => $prospect2->id]);

        $response = $this->actingAs($user)->get(route('quotes.index', ['client_id' => $prospect1->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 2)
            ->where('quotes.data.0.client_id', $prospect1->id)
            ->where('quotes.data.1.client_id', $prospect1->id)
        );
    }

    public function test_quotes_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);

        Quote::factory()->count(2)->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);
        Quote::factory()->count(1)->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Envoyé]);

        $response = $this->actingAs($user)->get(route('quotes.index', ['status' => QuoteStatus::Brouillon->value]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 2)
            ->where('quotes.data.0.status', QuoteStatus::Brouillon->value)
            ->where('quotes.data.1.status', QuoteStatus::Brouillon->value)
        );
    }

    public function test_quotes_list_is_paginated(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        Quote::factory()->count(15)->create(['user_id' => $user->id, 'client_id' => $prospect->id]); // 15 quotes, default pagination is 10

        $response = $this->actingAs($user)->get(route('quotes.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 10) // First page should have 10 items
            ->where('quotes.total', 15) // Total should be 15
        );

        $response = $this->actingAs($user)->get(route('quotes.index', ['page' => 2]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Quotes/Index')
            ->has('quotes.data', 5) // Second page should have 5 items
        );
    }

    public function test_authenticated_user_can_view_their_quote_edit_form(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id]);

        $response = $this->actingAs($user)->get(route('quotes.edit', $quote->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DocumentBuilder')
            ->has('quote')
            ->has('templates')
        );
    }

    public function test_authenticated_user_cannot_view_another_users_quote_edit_form(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);
        $quote = Quote::factory()->create(['user_id' => $otherUser->id, 'client_id' => $prospect->id]);

        $response = $this->actingAs($user)->get(route('quotes.edit', $quote->id));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_update_their_quote(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $lineItems = [
            [
                'description' => 'Updated Service A',
                'quantity' => 3,
                'unit_price' => 120.00,
                'vat_rate' => 20.00,
            ],
            [
                'description' => 'New Service C', // New line item
                'quantity' => 1,
                'unit_price' => 75.00,
                'vat_rate' => 10.00,
            ],
        ];

        $response = $this->actingAs($user)->put(route('quotes.update', $quote->id), [
            'status' => QuoteStatus::Envoyé->value,
            'expires_at' => now()->addDays(10)->format('Y-m-d'),
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Devis mis à jour avec succès.');
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => QuoteStatus::Envoyé,
            'subtotal' => 435.00, // (3*120) + (1*75) = 360 + 75 = 435
            'vat_amount' => 79.50, // (360*0.20) + (75*0.10) = 72 + 7.5 = 79.5
            'total' => 514.50, // 435 + 79.5
        ]);

        $this->assertDatabaseHas('quote_line_items', [
            'quote_id' => $quote->id,
            'description' => 'Updated Service A',
            'quantity' => 3,
            'unit_price' => 120.00,
            'vat_rate' => 20.00,
        ]);
        $this->assertDatabaseHas('quote_line_items', [
            'quote_id' => $quote->id,
            'description' => 'New Service C',
            'quantity' => 1,
            'unit_price' => 75.00,
            'vat_rate' => 10.00,
        ]);
    }

    public function test_authenticated_user_cannot_update_another_users_quote(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);
        $quote = Quote::factory()->create(['user_id' => $otherUser->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $response = $this->actingAs($user)->put(route('quotes.update', $quote->id), [
            'status' => QuoteStatus::Envoyé->value,
            'expires_at' => now()->addDays(10)->format('Y-m-d'),
            'line_items' => [
                ['description' => 'Test Service', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => QuoteStatus::Brouillon]); // Status should not change
    }

    public function test_quote_can_only_be_updated_if_in_brouillon_status(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Envoyé]); // Not Brouillon

        $lineItems = [
            [
                'description' => 'Updated Service A',
                'quantity' => 3,
                'unit_price' => 120.00,
                'vat_rate' => 20.00,
            ],
        ];

        $response = $this->actingAs($user)->put(route('quotes.update', $quote->id), [
            'status' => QuoteStatus::Brouillon->value, // Attempt to change to Brouillon
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Seuls les devis en statut Brouillon peuvent être modifiés.');
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => QuoteStatus::Envoyé]); // Status should not change
    }

    public function test_quote_update_validation_rules(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        $response = $this->actingAs($user)->put(route('quotes.update', $quote->id), [
            'status' => 'InvalidStatus',
            'expires_at' => 'not-a-date',
            'line_items' => [], // Empty line items
        ]);

        $response->assertSessionHasErrors(['status', 'expires_at', 'line_items']);
        $response->assertStatus(302);
    }

    public function test_authenticated_user_can_duplicate_their_quote(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Envoyé]);
        $quote->lineItems()->createMany([
            ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 10.00, 'vat_rate' => 20.00, 'total' => 12.00],
            ['description' => 'Item 2', 'quantity' => 2, 'unit_price' => 5.00, 'vat_rate' => 10.00, 'total' => 11.00],
        ]);

        $response = $this->actingAs($user)->post(route('quotes.duplicate', $quote->id));

        $response->assertRedirect(route('quotes.edit', 2)); // Assuming the new quote has ID 2
        $response->assertSessionHas('success', 'Devis dupliqué avec succès.');

        $newQuote = Quote::orderBy('id', 'desc')->first(); // Get the newly created quote by ID
        $this->assertNotNull($newQuote);
        $this->assertNotEquals($quote->id, $newQuote->id);
        $this->assertEquals($quote->client_id, $newQuote->client_id);
        $this->assertEquals($user->id, $newQuote->user_id);
        $this->assertEquals(QuoteStatus::Brouillon, $newQuote->status); // Duplicated quote is always Brouillon
        $this->assertNull($newQuote->expires_at); // Expiration date is cleared
        $this->assertNotNull($newQuote->quote_number); // New quote number
        $this->assertNotEquals($quote->quote_number, $newQuote->quote_number); // New unique quote number

        $this->assertCount(2, $newQuote->lineItems);
        foreach ($newQuote->lineItems as $index => $newLineItem) {
            $originalLineItem = $quote->lineItems[$index];
            $this->assertEquals($originalLineItem->description, $newLineItem->description);
            $this->assertEquals($originalLineItem->quantity, $newLineItem->quantity);
            $this->assertEquals($originalLineItem->unit_price, $newLineItem->unit_price);
            $this->assertEquals($originalLineItem->vat_rate, $newLineItem->vat_rate);
        }
    }

    public function test_authenticated_user_cannot_duplicate_another_users_quote(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);
        $quote = Quote::factory()->create(['user_id' => $otherUser->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Envoyé]);

        $response = $this->actingAs($user)->post(route('quotes.duplicate', $quote->id));

        $response->assertForbidden();
        $this->assertDatabaseCount('quotes', 1); // Only the original quote should exist
    }

    public function test_duplicated_quote_has_brouillon_status_and_new_number(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Envoyé]);

        $response = $this->actingAs($user)->post(route('quotes.duplicate', $quote->id));
        $response->assertRedirect();

        $newQuote = Quote::orderBy('id', 'desc')->first(); // Get the newly created quote by ID
        $this->assertEquals(QuoteStatus::Brouillon, $newQuote->status);
        $this->assertNotNull($newQuote->quote_number);
        $this->assertNotEquals($quote->quote_number, $newQuote->quote_number);
    }

    public function test_quote_cannot_be_updated_if_already_converted_to_invoice(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Brouillon]);

        // Mock conversion by creating an invoice linked to this quote
        Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
            'quote_id' => $quote->id,
        ]);

        $lineItems = [
            ['description' => 'Updated Service', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->put(route('quotes.update', $quote->id), [
            'status' => QuoteStatus::Brouillon->value,
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Ce devis ne peut pas être modifié car il a déjà été converti en facture.');
    }

    public function test_quote_status_cannot_be_updated_if_already_converted_to_invoice(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id, 'status' => QuoteStatus::Approuvé]);

        // Mock conversion by creating an invoice linked to this quote
        Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
            'quote_id' => $quote->id,
        ]);

        $response = $this->actingAs($user)->patch(route('quotes.updateStatus', $quote->id), [
            'status' => QuoteStatus::Brouillon->value,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Le statut d\'un devis converti en facture ne peut pas être modifié.');
        $this->assertEquals(QuoteStatus::Approuvé, $quote->fresh()->status);
    }

    public function test_pdf_generation_job_is_dispatched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create(['user_id' => $user->id, 'client_id' => $prospect->id]);

        $response = $this->actingAs($user)->post(route('quotes.generatePdf', $quote->id));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'La génération du PDF a été lancée. Le fichier sera disponible sous peu.');

        Queue::assertPushed(GenerateQuotePdfJob::class, function ($job) use ($quote) {
            return $job->quote->id === $quote->id;
        });
    }

    public function test_quote_can_be_converted_to_invoice(): void
    {
        $user = User::factory()->create();
        // Ensure the user has a company profile with an invoice number reset frequency
        $user->companyProfile()->create(['invoice_number_reset_frequency' => 'yearly']);
        // Initialize invoice number for the user
        \App\Models\InvoiceNumber::create([
            'user_id' => $user->id,
            'pattern' => 'INV-{YEAR}-{SEQUENCE}',
            'reset_frequency' => 'yearly',
            'last_generated_at' => now(),
        ]);
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
            'status' => QuoteStatus::Approuvé, // Must be approved
        ]);
        $quote->lineItems()->create([
            'description' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 240.00,
        ]);

        $response = $this->actingAs($user)->post(route('quotes.convertToInvoice', $quote->id));

        $invoice = Invoice::where('quote_id', $quote->id)->first();
        $this->assertNotNull($invoice);

        $response->assertRedirect(route('invoices.show', $invoice->id));
        $response->assertSessionHas('success', 'Devis converti en facture avec succès.');

        $this->assertEquals($quote->client_id, $invoice->client_id);
        $this->assertEquals($quote->user_id, $invoice->user_id);
        $this->assertEquals($quote->subtotal, $invoice->subtotal);
        $this->assertEqualsWithDelta($quote->vat_amount, $invoice->vat_amount, 0.001);
        $this->assertEqualsWithDelta($quote->total, $invoice->total, 0.001);
        $this->assertEquals(InvoiceStatus::Brouillon->value, $invoice->status->value);
        $this->assertNotNull($invoice->invoice_number);

        $this->assertDatabaseHas('invoice_line_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Test Item',
            'quantity' => 2,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 240.00,
        ]);
    }

    public function test_only_approved_quotes_can_be_converted_to_invoice(): void
    {
        $user = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $user->id]);
        $quote = Quote::factory()->create([
            'user_id' => $user->id,
            'client_id' => $prospect->id,
            'status' => QuoteStatus::Brouillon, // Not approved
        ]);

        $response = $this->actingAs($user)->post(route('quotes.convertToInvoice', $quote->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Seuls les devis approuvés peuvent être convertis en facture.');
        $this->assertDatabaseMissing('invoices', ['quote_id' => $quote->id]);
    }

    public function test_user_cannot_convert_another_users_quote_to_invoice(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $prospect = Prospect::factory()->create(['user_id' => $otherUser->id]);
        $quote = Quote::factory()->create([
            'user_id' => $otherUser->id,
            'client_id' => $prospect->id,
            'status' => QuoteStatus::Approuvé,
        ]);

        $response = $this->actingAs($user)->post(route('quotes.convertToInvoice', $quote->id));

        $response->assertForbidden();
        $this->assertDatabaseMissing('invoices', ['quote_id' => $quote->id]);
    }
}
