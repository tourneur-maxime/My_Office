<?php

namespace Tests\Feature\Settings;

use App\Models\Invoice;
use App\Models\User;
use App\Models\InvoiceNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.invoices.numbering'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Invoices/Numbering')
            ->has('settings')
            ->has('preview')
        );
    }

    public function test_settings_can_be_updated_when_no_invoices_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('settings.invoices.numbering'))
            ->patch(route('settings.invoices.numbering.update'), [
                'prefix' => 'NEW',
                'suffix' => 'Z',
                'digit_count' => 5,
                'reset_frequency' => 'yearly',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('settings.invoices.numbering'));

        $this->assertDatabaseHas('invoice_numbers', [
            'user_id' => $user->id,
            'prefix' => 'NEW',
            'suffix' => 'Z',
            'digit_count' => 5,
            'reset_frequency' => 'yearly',
        ]);
    }

    public function test_settings_cannot_be_updated_if_invoices_exist_for_period(): void
    {
        $user = User::factory()->create();
        $settings = InvoiceNumber::factory()->create([
            'user_id' => $user->id,
            'current_number' => 1,
            'counter_year' => now()->year,
        ]);
        Invoice::factory()->create([
            'user_id' => $user->id,
            'issue_date' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('settings.invoices.numbering'))
            ->patch(route('settings.invoices.numbering.update'), [
                'prefix' => 'DIFFERENT', // Attempt to change
                'suffix' => $settings->suffix,
                'digit_count' => $settings->digit_count,
                'reset_frequency' => $settings->reset_frequency,
            ]);

        $response->assertSessionHasErrors(['settings']);
    }

    public function test_validation_rules(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('settings.invoices.numbering.update'), [
            'prefix' => '', // Allowed
            'digit_count' => 1, // min 3
        ]);

        $response->assertSessionHasErrors(['digit_count']);
    }
}
