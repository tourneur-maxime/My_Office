<?php

namespace Database\Seeders;

use Guava\Sequence\Enums\ResetFrequency;
use Guava\Sequence\Models\SequenceRule;
use Illuminate\Database\Seeder;

class QuoteSequenceRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SequenceRule::firstOrCreate(
            ['type' => 'quote'],
            [
                'pattern' => 'QUO-{YEAR}-{SEQUENCE}', // Default pattern
                'reset_frequency' => ResetFrequency::Yearly,
            ]
        );
    }
}
