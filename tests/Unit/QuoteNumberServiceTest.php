<?php

namespace Tests\Unit;

use App\Models\QuoteNumberSetting;
use App\Models\User;
use App\Services\QuoteNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private QuoteNumberService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuoteNumberService();
        $this->user = User::factory()->create();
    }

    public function test_it_generates_default_format()
    {
        $number = $this->service->getNext($this->user);
        $year = date('Y');
        $this->assertEquals("DEV-{$year}-0001", $number);
    }

    public function test_it_increments_sequentially()
    {
        $this->service->getNext($this->user);
        $number = $this->service->getNext($this->user);
        $year = date('Y');
        $this->assertEquals("DEV-{$year}-0002", $number);
    }

    public function test_it_handles_custom_prefix_and_no_year()
    {
        QuoteNumberSetting::create([
            'user_id' => $this->user->id,
            'prefix' => 'QUO',
            'include_year' => false,
            'digit_count' => 3,
            'last_number' => 10
        ]);

        $number = $this->service->getNext($this->user);
        $this->assertEquals("QUO-011", $number);
    }

    public function test_it_handles_suffix()
    {
        QuoteNumberSetting::create([
            'user_id' => $this->user->id,
            'prefix' => 'DEV',
            'suffix' => 'MT',
            'include_year' => true,
            'digit_count' => 3,
            'last_number' => 5
        ]);

        $number = $this->service->getNext($this->user);
        $year = date('Y');
        $this->assertEquals("DEV-{$year}-006-MT", $number);
    }

    public function test_it_previews_without_incrementing()
    {
        $preview = $this->service->previewNext($this->user);
        $year = date('Y');
        $this->assertEquals("DEV-{$year}-0001", $preview);

        $number = $this->service->getNext($this->user);
        $this->assertEquals("DEV-{$year}-0001", $number);

        $preview2 = $this->service->previewNext($this->user);
        $this->assertEquals("DEV-{$year}-0002", $preview2);
    }

    public function test_it_resets_counter_when_year_changes()
    {
        $year = date('Y');

        // Generate first number in current year
        $number1 = $this->service->getNext($this->user);
        $this->assertEquals("DEV-{$year}-0001", $number1);

        // Manually update counter_year to simulate year change
        $settings = QuoteNumberSetting::where('user_id', $this->user->id)->first();
        $settings->update(['counter_year' => (int) $year - 1]);

        // Next call should detect year change and reset counter
        $number2 = $this->service->getNext($this->user);
        $this->assertEquals("DEV-{$year}-0001", $number2);

        // Verify counter was reset
        $settings->refresh();
        $this->assertEquals((int) $year, $settings->counter_year);
        $this->assertEquals(1, $settings->last_number);
    }
}