<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // Import the Enum

class QuoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Quote::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'client_id' => Prospect::factory(),
            'user_id' => User::factory(),
            // Changed to use enum cases
            'status' => $this->faker->randomElement(QuoteStatus::cases()),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'quote_number' => 'QUO-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'subtotal' => $this->faker->randomFloat(2, 50, 1000),
            'vat_amount' => $this->faker->randomFloat(2, 10, 200),
            'total' => $this->faker->randomFloat(2, 100, 1200),
        ];
    }
}
