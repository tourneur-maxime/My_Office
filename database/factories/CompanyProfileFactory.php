<?php

namespace Database\Factories;

use App\Models\CompanyProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyProfileFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompanyProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->streetAddress,
            'zip_code' => $this->faker->postcode,
            'city' => $this->faker->city,
            'siret' => $this->faker->numerify('##############'), // 14 digits
            'siren' => $this->faker->numerify('#########'), // 9 digits
            'vat_number' => 'FR'.$this->faker->numerify('##########'), // FR + 11 digits
            'rcs_number' => $this->faker->bothify('RCS ### #### ###'),
            'legal_form' => $this->faker->randomElement(['SARL', 'EURL', 'SAS', 'SASU', 'Auto-entrepreneur']),
            'share_capital' => $this->faker->numberBetween(1000, 100000),
            'payment_terms' => $this->faker->randomElement(['30 jours', '60 jours', 'Comptant']),
            'is_vat_exempt' => $this->faker->boolean,
            'custom_legal_mentions' => $this->faker->paragraph,
            'vat_status' => $this->faker->randomElement(['assujetti', 'non assujetti']),
            'invoice_numbering_format' => 'FAC-{YYYY}-{number:4}',
            'invoice_number_reset_frequency' => $this->faker->randomElement(['yearly', 'never']),
            'quote_numbering_format' => 'DEV-{YYYY}-{number:4}',
            'logo_path' => null,
            'primary_color' => $this->faker->hexColor,
            'secondary_color' => $this->faker->hexColor,
            'font_family' => $this->faker->randomElement(['Arial', 'Verdana', 'Helvetica']),
        ];
    }
}
