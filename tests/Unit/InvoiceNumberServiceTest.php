<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Models\InvoiceNumber;
use App\Models\User;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceNumberService $invoiceNumberService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceNumberService = new InvoiceNumberService();
    }

    public function test_it_generates_sequential_invoice_numbers_for_user(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());

        $firstNumber = $this->invoiceNumberService->getNextInvoiceNumber($user);
        $secondNumber = $this->invoiceNumberService->getNextInvoiceNumber($user);

        preg_match('/-(\d+)$/', $firstNumber, $matches1);
        preg_match('/-(\d+)$/', $secondNumber, $matches2);

        $this->assertNotNull($matches1);
        $this->assertNotNull($matches2);
        $this->assertEquals(1, (int) $matches1[1]); // Ensure it starts from 1
        $this->assertEquals(2, (int) $matches2[1]); // Ensure it increments to 2
        $this->assertEquals((int) $matches1[1] + 1, (int) $matches2[1]);
    }

    public function test_it_generates_gapless_sequential_invoice_numbers_under_concurrent_load(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());

        $expectedNumbers = 10;
        $generatedNumbers = [];

        // Simulate concurrent requests
        for ($i = 0; $i < $expectedNumbers; $i++) {
            $generatedNumbers[] = $this->app->make(InvoiceNumberService::class)->getNextInvoiceNumber($user);
        }

        // Extract just the numerical part for comparison
        $numbers = array_map(function ($num) {
            preg_match('/-(\d+)$/', $num, $matches);
            return (int) $matches[1];
        }, $generatedNumbers);

        sort($numbers); // Sort to ensure correct order for comparison

        // Assert that numbers are sequential and gapless
        for ($i = 0; $i < $expectedNumbers - 1; $i++) {
            $this->assertEquals($numbers[$i] + 1, $numbers[$i + 1], "Gap found between {$numbers[$i]} and {$numbers[$i + 1]}");
        }

        $this->assertCount($expectedNumbers, array_unique($generatedNumbers), 'Numbers should be unique and gapless');
        $this->assertEquals(1, $numbers[0]); // Ensure it starts from 1
        $this->assertEquals($expectedNumbers, $numbers[$expectedNumbers - 1]); // Ensure it reaches the expected number
    }

    public function test_invoice_number_uses_custom_format_from_settings(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());
        
        // Setup custom settings
        InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => 'CUSTOM',
            'digit_count' => 3,
            'current_number' => 0,
            'counter_year' => Carbon::now()->year,
            'reset_frequency' => 'yearly',
        ]);

        $invoiceNumber = $this->invoiceNumberService->getNextInvoiceNumber($user);

        $year = Carbon::now()->year;
        $this->assertMatchesRegularExpression("/^CUSTOM-{$year}-\d{3}$/", $invoiceNumber);
        preg_match('/-(\d+)$/', $invoiceNumber, $matches);
        $this->assertEquals(1, (int) $matches[1]); // Ensure it starts from 1 with custom format
    }

    public function test_multiple_users_have_independent_invoice_number_sequences(): void
    {
        $user1 = User::factory()->create();
        $user1->companyProfile()->create(CompanyProfile::factory()->make()->toArray());
        $user2 = User::factory()->create();
        $user2->companyProfile()->create(CompanyProfile::factory()->make()->toArray());

        $firstNumberUser1 = $this->invoiceNumberService->getNextInvoiceNumber($user1); // Should be ...0001
        $firstNumberUser2 = $this->invoiceNumberService->getNextInvoiceNumber($user2); // Should be ...0001

        $secondNumberUser1 = $this->invoiceNumberService->getNextInvoiceNumber($user1); // Should be ...0002
        $secondNumberUser2 = $this->invoiceNumberService->getNextInvoiceNumber($user2); // Should be ...0002

        // Verify user1's sequence increments independently
        preg_match('/-(\d+)$/', $firstNumberUser1, $matches1_1);
        preg_match('/-(\d+)$/', $secondNumberUser1, $matches1_2);
        $this->assertEquals(1, (int) $matches1_1[1]);
        $this->assertEquals(2, (int) $matches1_2[1]);

        // Verify user2's sequence increments independently
        preg_match('/-(\d+)$/', $firstNumberUser2, $matches2_1);
        preg_match('/-(\d+)$/', $secondNumberUser2, $matches2_2);
        $this->assertEquals(1, (int) $matches2_1[1]);
        $this->assertEquals(2, (int) $matches2_2[1]);

        // Assert that the first numbers are the same (each starts from 1) but belong to different users
        // NOTE: Standard prefix is FAC-YYYY-
        $year = Carbon::now()->year;
        $expectedFirst = "FAC-{$year}-0001";
        $expectedSecond = "FAC-{$year}-0002";
        
        $this->assertEquals($expectedFirst, $firstNumberUser1); 
        $this->assertEquals($expectedFirst, $firstNumberUser2); 
        $this->assertEquals($expectedSecond, $secondNumberUser1); 
        $this->assertEquals($expectedSecond, $secondNumberUser2); 
        
        // Assert that calling getNextInvoiceNumber again for user1 gives the next number in user1's sequence
        $this->assertEquals("FAC-{$year}-0003", $this->invoiceNumberService->getNextInvoiceNumber($user1));
    }

    public function test_invoice_number_record_is_created_if_not_exists(): void
    {
        $user = User::factory()->create();
        $user->companyProfile()->create(CompanyProfile::factory()->make()->toArray());

        $this->assertDatabaseMissing('invoice_numbers', ['user_id' => $user->id]);
        $this->invoiceNumberService->getNextInvoiceNumber($user);
        $this->assertDatabaseHas('invoice_numbers', [
            'user_id' => $user->id, 
            'prefix' => 'FAC', 
            'current_number' => 1
        ]);
    }

    public function test_sequence_resets_yearly_if_configured(): void
    {
        // Mock Carbon to control time
        Carbon::setTestNow('2024-12-31 23:59:59');

        $user = User::factory()->create();
        
        // Use InvoiceNumber settings instead of CompanyProfile
        InvoiceNumber::create([
            'user_id' => $user->id,
            'prefix' => 'FAC',
            'reset_frequency' => 'yearly',
            'counter_year' => 2024,
            'current_number' => 0
        ]);
        
        $firstNumber2024 = $this->invoiceNumberService->getNextInvoiceNumber($user); // N=1 in 2024
        $this->assertStringContainsString('FAC-2024-0001', $firstNumber2024);

        $secondNumber2024 = $this->invoiceNumberService->getNextInvoiceNumber($user); // N=2 in 2024
        $this->assertStringContainsString('FAC-2024-0002', $secondNumber2024);


        Carbon::setTestNow('2025-01-01 00:00:01'); // Move to next year

        $firstNumber2025 = $this->invoiceNumberService->getNextInvoiceNumber($user); // N=1 in 2025
        $this->assertStringContainsString('FAC-2025-0001', $firstNumber2025);

        // Ensure current_number was reset to 0 before incrementing to 1
        $this->assertDatabaseHas('invoice_numbers', [
            'user_id' => $user->id,
            'counter_year' => 2025,
            'current_number' => 1,
        ]);
    }
}
