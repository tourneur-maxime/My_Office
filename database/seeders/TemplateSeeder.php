<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::all()->each(function ($user) {
            Template::firstOrCreate(
                ['user_id' => $user->id, 'name' => 'Default'],
                [
                    'primary_color' => '#3B82F6',
                    'secondary_color' => '#10B981',
                    'font' => 'Inter',
                    'is_default' => true,
                ]
            );
        });
    }
}
