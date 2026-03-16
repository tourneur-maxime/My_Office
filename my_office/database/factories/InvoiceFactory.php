<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Prospect;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory; // Import the Enum

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

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
            'quote_id' => Quote::factory(),
            'invoice_number' => 'INV-'.date('Y').'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'issue_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'due_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            // Changed to use enum cases
            'status' => $this->faker->randomElement(InvoiceStatus::cases()),
            'subtotal' => $this->faker->randomFloat(2, 50, 1000),
            'vat_amount' => $this->faker->randomFloat(2, 10, 200),
            'total' => $this->faker->randomFloat(2, 100, 1200),
            'paid_at' => $this->faker->optional()->dateTimeBetween('-2 weeks', 'now'),
        ];
    }
}
