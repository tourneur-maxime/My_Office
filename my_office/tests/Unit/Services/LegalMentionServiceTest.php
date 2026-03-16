<?php

namespace Tests\Unit\Services;

use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\User;
use App\Services\LegalMentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class LegalMentionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LegalMentionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LegalMentionService();
    }

    public function test_get_mentions_includes_vat_exempt_and_micro_entrepreneur_for_vat_exempt_profile()
    {
        $user = User::factory()->create();
        $profile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'is_vat_exempt' => true, 
        ]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
        ]);
        // Mock the relationship loading as it would happen in app
        $invoice->setRelation('user', $user);
        $user->setRelation('companyProfile', $profile);

        $mentions = $this->service->getMentions($invoice);

        $this->assertContains(config('legal.vat_exempt'), $mentions);
        $this->assertContains(config('legal.micro_entrepreneur'), $mentions);
        $this->assertContains(config('legal.late_penalty'), $mentions);
    }

    public function test_get_mentions_excludes_vat_exempt_for_standard_profile()
    {
        $user = User::factory()->create();
        $profile = CompanyProfile::factory()->create([
            'user_id' => $user->id,
            'is_vat_exempt' => false, 
        ]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
        ]);
        $invoice->setRelation('user', $user);
        $user->setRelation('companyProfile', $profile);

        $mentions = $this->service->getMentions($invoice);

        $this->assertNotContains(config('legal.vat_exempt'), $mentions);
        $this->assertNotContains(config('legal.micro_entrepreneur'), $mentions); // Assuming false implies standard corp
        $this->assertContains(config('legal.late_penalty'), $mentions);
    }
}
