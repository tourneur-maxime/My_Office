<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_validates_compliance_data_successfully(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'siret' => '12345678901234',
        ]);
        
        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'siret' => '98765432109876',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.compliance.validate'), [
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2026-0001',
            'line_items' => [
                [
                    'description' => 'Service A',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'vat_rate' => 20,
                ]
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.statuses.xml', 'valid');
    }

    public function test_it_reports_missing_company_profile(): void
    {
        $user = User::factory()->create();
        // No company profile
        
        $client = Prospect::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('api.compliance.validate'), [
            'client_id' => $client->id,
            'line_items' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.valid', false)
            ->assertJsonFragment(['company' => ["Le profil d'entreprise est manquant."]]);
    }

    public function test_it_reports_invalid_client(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)->postJson(route('api.compliance.validate'), [
            'client_id' => 9999, // Non-existent client
            'line_items' => [],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(false, $response->json('data.valid'));
        $this->assertArrayHasKey('client_id', $response->json('data.errors'));
    }

    public function test_it_runs_schematron_validation(): void
    {
        $user = User::factory()->create();
        CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'siret' => '12345678901234',
        ]);

        $client = Prospect::factory()->create([
            'user_id' => $user->id,
            'siret' => '98765432109876',
        ]);

        $response = $this->actingAs($user)->postJson(route('api.compliance.validate'), [
            'client_id' => $client->id,
            'invoice_number' => 'FAC-2026-0001',
            'line_items' => [
                [
                    'description' => 'Service A',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'vat_rate' => 20,
                ]
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // The generated XML from horstoeko/zugferd should pass both XSD and Schematron
        $response->assertJsonPath('data.statuses.schematron', 'valid');
    }
}