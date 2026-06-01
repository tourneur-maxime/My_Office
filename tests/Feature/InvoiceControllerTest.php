<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
// Add for file operations
use Spatie\Activitylog\Models\Activity; // Add for file operations
use Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_view_invoice_creation_form_for_their_client(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $response = $this->actingAs($user)->get(route('invoices.create', $client->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('DocumentBuilder')
            ->has('client')
        );
    }

    public function test_authenticated_user_cannot_view_invoice_creation_form_for_another_users_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $otherUser->id, 'status' => 'client']);

        $response = $this->actingAs($user)->get(route('invoices.create', $client->id));

        $response->assertForbidden();
    }

    public function test_authenticated_user_can_create_an_invoice_with_line_items_for_their_client(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $lineItems = [
            [
                'description' => 'Service X',
                'quantity' => 2,
                'unit_price' => 100.00,
                'vat_rate' => 20.00,
            ],
            [
                'description' => 'Produit Y',
                'quantity' => 1,
                'unit_price' => 50.00,
                'vat_rate' => 10.00,
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('success', 'Facture créée avec succès.');

        $this->assertDatabaseHas('invoices', [
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => 'Brouillon',
            'subtotal' => 250.00, // (2*100) + (1*50)
            'vat_amount' => 45.00, // (200*0.20) + (50*0.10) = 40 + 5 = 45
            'total' => 295.00, // 250 + 45
        ]);

        $invoice = $client->invoices()->latest()->first(); // Get the newly created invoice

        $this->assertDatabaseHas('invoice_line_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Service X',
            'quantity' => 2,
            'unit_price' => 100.00,
            'vat_rate' => 20.00,
            'total' => 240.00, // 2 * 100 * (1 + 0.20)
        ]);
        $this->assertDatabaseHas('invoice_line_items', [
            'invoice_id' => $invoice->id,
            'description' => 'Produit Y',
            'quantity' => 1,
            'unit_price' => 50.00,
            'vat_rate' => 10.00,
            'total' => 55.00, // 1 * 50 * (1 + 0.10)
        ]);
    }

    public function test_authenticated_user_cannot_create_an_invoice_for_another_users_client(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $otherUser->id, 'status' => 'client']);

        $lineItems = [
            [
                'description' => 'Service X',
                'quantity' => 1,
                'unit_price' => 100.00,
                'vat_rate' => 20.00,
            ],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('invoices', ['client_id' => $client->id]);
    }

    public function test_invoice_creation_requires_line_items(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
            'line_items' => [], // Empty line items
        ]);

        $response->assertSessionHasErrors('line_items');
        $response->assertStatus(302);
    }

    public function test_invoice_line_item_validation_rules(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
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

    public function test_invoice_number_format_is_customizable_from_settings(): void
    {
        $user = User::factory()->create();
        // Ensure the user has a company profile (mandatory)
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());
        
        // Setup InvoiceNumber settings directly
        \App\Models\InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => 'INV',
            'digit_count' => 5,
            'current_number' => 0,
            'counter_year' => Carbon::now()->year
        ]);
        
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $this->actingAs($user)->post(route('invoices.store', $client->id), ['line_items' => $lineItems]);

        $invoice = Invoice::where('client_id', $client->id)->first();
        $this->assertNotNull($invoice->invoice_number);

        $year = date('Y');
        $this->assertStringStartsWith("INV-{$year}-", $invoice->invoice_number);
    }

    public function test_invoice_number_cannot_be_changed_after_assignment(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'invoice_number' => 'ORIGINAL-123']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice number cannot be changed once assigned.');

        $invoice->invoice_number = 'NEW-ABC';
        $invoice->save();
    }

    public function test_invoice_creation_is_logged_in_activity_log(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $lineItems = [
            ['description' => 'Service X', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertRedirect();

        $invoice = Invoice::where('client_id', $client->id)->first();

        $this->assertCount(1, Activity::all());
        $activity = Activity::first();
        $this->assertEquals('created', $activity->description);
        $this->assertEquals($user->id, $activity->causer_id);
        $this->assertEquals($invoice->id, $activity->subject_id);
        $this->assertEquals(Invoice::class, $activity->subject_type);
        $this->assertArrayHasKey('invoice_number', $activity->changes['attributes']);
        $this->assertEquals($invoice->invoice_number, $activity->changes['attributes']['invoice_number']);
    }

    public function test_invoice_creation_fails_without_mandatory_company_profile_fields(): void
    {
        $user = User::factory()->create();
        // Create an incomplete company profile for the user, missing 'name'
        // This will now cause the validation in the controller to fail
        CompanyProfile::factory()->create([
            'user_id' => $user->id, // Correctly associate with the user
            'name' => null, // Intentionally set name to null
            'address' => '123 Main St',
            'siret' => '12345678901234',
            'legal_form' => 'SARL',
            'invoice_numbering_format' => 'INV-{YYYY}-{number:5}',
        ]);
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);

        $lineItems = [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 100.00, 'vat_rate' => 20.00],
        ];

        $response = $this->actingAs($user)->post(route('invoices.store', $client->id), [
            'line_items' => $lineItems,
        ]);

        $response->assertSessionHas('error', 'Veuillez compléter les informations obligatoires de votre profil d\'entreprise pour créer une facture : Raison sociale de l\'entreprise');
        $response->assertRedirect();
    }

    public function test_generated_pdf_contains_basic_legal_mentions(): void
    {
        Storage::fake('public'); // Mock the storage for PDF generation

        $user = User::factory()->create();
        $user->companyProfile()->create(
            CompanyProfile::factory()->make([
                'name' => 'Ma Super Entreprise',
                'address' => '10 Rue de la Paix, 75000 Paris',
                'siret' => '12345678901234',
                'rcs_number' => 'Paris B 123456789',
                'legal_form' => 'SARL',
                'share_capital' => 10000,
                'vat_number' => 'FR00123456789',
                'is_vat_exempt' => false,
                'payment_terms' => '30 jours fin de mois',
            ])->toArray()
        );
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        $invoice->lineItems()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20, 'total' => 120]);

        // Access the download method directly to get the generated PDF content
        $response = $this->actingAs($user)->get(route('invoices.download', $invoice->id));
        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));

        $pdfContent = $response->getContent();

        $this->assertStringStartsWith('%PDF', $pdfContent);
    }

    public function test_generated_pdf_contains_auto_entrepreneur_vat_exempt_mention(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->companyProfile()->create(
            CompanyProfile::factory()->make([
                'name' => 'Mon Auto-Entreprise',
                'address' => '5 Rue du Commerce, 34000 Montpellier',
                'siret' => '98765432109876',
                'legal_form' => 'Auto-entrepreneur',
                'is_vat_exempt' => true,
                'payment_terms' => 'Comptant',
            ])->toArray()
        );
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        $invoice->lineItems()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 0, 'total' => 100]);

        $response = $this->actingAs($user)->get(route('invoices.download', $invoice->id));
        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));

        $pdfContent = $response->getContent();

        $this->assertStringStartsWith('%PDF', $pdfContent);
    }

    public function test_generated_pdf_contains_late_payment_clause(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $user->companyProfile()->create(
            CompanyProfile::factory()->make([
                'name' => 'Test Company',
                'address' => 'Test Address',
                'siret' => '12345678901234',
                'legal_form' => 'SARL',
                'payment_terms' => '45 jours fin de mois',
            ])->toArray()
        );
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);
        $invoice->lineItems()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20, 'total' => 120]);

        $response = $this->actingAs($user)->get(route('invoices.download', $invoice->id));
        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));

        $pdfContent = $response->getContent();

        $this->assertStringStartsWith('%PDF', $pdfContent);
    }

    public function test_authenticated_user_can_view_their_invoices_list(): void
    {
        $user = User::factory()->create();
        Invoice::factory()->count(3)->create(['user_id' => $user->id, 'client_id' => Prospect::factory()->create(['user_id' => $user->id])->id]);

        $response = $this->actingAs($user)->get(route('invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 3)
            ->has('filters')
            ->has('clients')
        );
    }

    public function test_invoices_list_can_be_filtered_by_client(): void
    {
        $user = User::factory()->create();
        $client1 = Prospect::factory()->create(['user_id' => $user->id]);
        $client2 = Prospect::factory()->create(['user_id' => $user->id]);

        Invoice::factory()->count(2)->create(['user_id' => $user->id, 'client_id' => $client1->id]);
        Invoice::factory()->count(1)->create(['user_id' => $user->id, 'client_id' => $client2->id]);

        $response = $this->actingAs($user)->get(route('invoices.index', ['client_id' => $client1->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 2)
            ->where('invoices.data.0.client_id', $client1->id)
            ->where('invoices.data.1.client_id', $client1->id)
        );
    }

    public function test_invoices_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);

        Invoice::factory()->count(2)->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Brouillon']);
        Invoice::factory()->count(1)->create(['user_id' => $user->id, 'client_id' => $client->id, 'status' => 'Payé']);

        $response = $this->actingAs($user)->get(route('invoices.index', ['status' => 'Brouillon']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 2)
            ->where('invoices.data.0.status', 'Brouillon')
            ->where('invoices.data.1.status', 'Brouillon')
        );
    }

    public function test_invoices_list_can_be_filtered_by_date_range(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);

        // Invoices for testing date range
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => Carbon::parse('2025-01-10')]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => Carbon::parse('2025-01-15')]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => Carbon::parse('2025-02-01')]);
        Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id, 'issue_date' => Carbon::parse('2025-02-10')]);

        // Filter for January
        $response = $this->actingAs($user)->get(route('invoices.index', ['date_from' => '2025-01-01', 'date_to' => '2025-01-31']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 2)
        );

        // Filter for February
        $response = $this->actingAs($user)->get(route('invoices.index', ['date_from' => '2025-02-01', 'date_to' => '2025-02-28']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 2)
        );
    }

    public function test_invoices_list_is_paginated(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id]);
        Invoice::factory()->count(20)->create(['user_id' => $user->id, 'client_id' => $client->id]); // 20 invoices, default pagination is 15

        $response = $this->actingAs($user)->get(route('invoices.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 15) // First page should have 15 items
            ->where('invoices.total', 20) // Total should be 20
        );

        $response = $this->actingAs($user)->get(route('invoices.index', ['page' => 2]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Index')
            ->has('invoices.data', 5) // Second page should have 5 items
        );
    }

    public function test_authenticated_user_can_view_their_invoice_details(): void
    {
        $user = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $user->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $user->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('invoices.show', $invoice->id));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Invoices/Show')
            ->has('invoice')
            ->where('invoice.id', $invoice->id)
        );
    }

    public function test_authenticated_user_cannot_view_another_users_invoice_details(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $client = Prospect::factory()->create(['user_id' => $otherUser->id, 'status' => 'client']);
        $invoice = Invoice::factory()->create(['user_id' => $otherUser->id, 'client_id' => $client->id]);

        $response = $this->actingAs($user)->get(route('invoices.show', $invoice->id));

        $response->assertForbidden();
    }
}
