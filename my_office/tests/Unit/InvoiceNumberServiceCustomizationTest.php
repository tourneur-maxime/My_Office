<?php

namespace Tests\Unit;

use App\Models\InvoiceNumber;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceNumberService();
    }

    public function test_it_uses_custom_prefix_suffix_and_digits()
    {
        $user = User::factory()->create();
        
        // Setup custom settings
        InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => 'MYINV',
            'suffix' => 'A',
            'digit_count' => 5,
            'current_number' => 0,
            'counter_year' => date('Y'),
            'reset_frequency' => 'yearly',
            'separator' => '-',
            'include_year' => true
        ]);

        $number = $this->service->getNextInvoiceNumber($user);

        // Expected format: Prefix + Year + Separator + Digits + Suffix
        // Example: MYINV-2026-00001A
        
        $year = date('Y');
        $expectedPattern = "/^MYINV-{$year}-\d{5}-A$/";
        
        $this->assertMatchesRegularExpression($expectedPattern, $number);
        $this->assertStringContainsString(str_pad('1', 5, '0', STR_PAD_LEFT), $number);
    }

    public function test_it_supports_custom_separator_and_no_year()
    {
        $user = User::factory()->create();
        
        // Setup custom settings: No year, custom separator '/'
        InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => 'FAC',
            'suffix' => '',
            'digit_count' => 4,
            'current_number' => 0,
            'counter_year' => date('Y'),
            'reset_frequency' => 'yearly',
            'separator' => '/',
            'include_year' => false
        ]);

        $number = $this->service->getNextInvoiceNumber($user);

        // Expected format: FAC/0001
        $this->assertEquals('FAC/0001', $number);
    }

    public function test_it_supports_no_separator_and_no_prefix()
    {
        $user = User::factory()->create();
         // Setup custom settings: No prefix, No separator, Year included
        InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => '',
            'suffix' => '',
            'digit_count' => 6,
            'current_number' => 10,
            'counter_year' => date('Y'),
            'reset_frequency' => 'yearly',
            'separator' => '', // Empty separator
            'include_year' => true
        ]);

        $number = $this->service->getNextInvoiceNumber($user);
        $year = date('Y');
        
        // Expected: 2026000011 (Year + Sequence)
        $this->assertEquals("{$year}000011", $number);
    }
}
