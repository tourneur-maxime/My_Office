<?php

namespace Database\Factories;

use App\Models\InvoiceNumber;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceNumber>
 */
class InvoiceNumberFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = InvoiceNumber::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'prefix' => 'FAC',
            'suffix' => null,
            'digit_count' => 4,
            'current_number' => 0,
            'counter_year' => now()->year,
            'reset_frequency' => 'yearly',
            'last_generated_at' => null,
        ];
    }
}
