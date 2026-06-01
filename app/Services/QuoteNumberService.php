<?php

namespace App\Services;

use App\Models\QuoteNumberSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuoteNumberService
{
    /**
     * Get the next sequential quote number based on user settings.
     * Implements annual reset to prevent number reuse across years.
     *
     * @param User $user
     * @return string
     */
    public function getNext(User $user): string
    {
        return DB::transaction(function () use ($user) {
            $settings = QuoteNumberSetting::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'prefix' => 'DEV',
                        'digit_count' => 4,
                        'include_year' => true,
                        'last_number' => 0,
                        'counter_year' => (int) date('Y')
                    ]
                );

            $current_year = (int) date('Y');

            // Reset counter if year has changed (annual reset)
            if ($settings->counter_year !== $current_year) {
                $settings->update([
                    'last_number' => 0,
                    'counter_year' => $current_year
                ]);
            }

            $next_number = $settings->last_number + 1;
            $settings->update(['last_number' => $next_number]);

            return $this->formatNumber($settings, $next_number);
        });
    }

    /**
     * Format the quote number based on settings.
     *
     * @param QuoteNumberSetting $settings
     * @param int $number
     * @return string
     */
    public function formatNumber(QuoteNumberSetting $settings, int $number): string
    {
        $parts = [];

        if ($settings->prefix) {
            $parts[] = $settings->prefix;
        }

        if ($settings->include_year) {
            $parts[] = date('Y');
        }

        $parts[] = str_pad((string) $number, $settings->digit_count, '0', STR_PAD_LEFT);

        if ($settings->suffix) {
            $parts[] = $settings->suffix;
        }

        return implode('-', $parts);
    }

    /**
     * Preview what the next number would look like without incrementing.
     *
     * @param User $user
     * @return string
     */
    public function previewNext(User $user): string
    {
        $settings = QuoteNumberSetting::firstOrNew(
            ['user_id' => $user->id],
            [
                'prefix' => 'DEV',
                'digit_count' => 4,
                'include_year' => true,
                'last_number' => 0,
                'counter_year' => (int) date('Y')
            ]
        );

        $current_year = (int) date('Y');

        // If year has changed, simulate the reset for preview
        if ($settings->counter_year !== $current_year) {
            return $this->formatNumber($settings, 1);
        }

        return $this->formatNumber($settings, $settings->last_number + 1);
    }
}
