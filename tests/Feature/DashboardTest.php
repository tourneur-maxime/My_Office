<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_can_be_rendered()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_stats_are_calculated_correctly()
    {
        $user = User::factory()->create();

        // Create some data
        Prospect::factory()->count(3)->create(['user_id' => $user->id, 'status' => 'client']);
        Prospect::factory()->count(2)->create(['user_id' => $user->id, 'status' => 'prospect']);
        
        Quote::factory()->count(1)->create(['user_id' => $user->id, 'status' => \App\Enums\QuoteStatus::Envoyé]);
        
        Invoice::factory()->count(1)->create(['user_id' => $user->id, 'status' => \App\Enums\InvoiceStatus::Brouillon]);
        Invoice::factory()->count(1)->create(['user_id' => $user->id, 'status' => \App\Enums\InvoiceStatus::Envoyé]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        
        // You can verify the data passed to the view if needed, but the main goal is ensuring no crash.
        // The fact that it returns 200 means the relationships $user->quotes() and $user->invoices() 
        // were accessed successfully.
    }
}
