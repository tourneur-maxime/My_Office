<?php

namespace App\Services;

use App\Models\InvoiceNumber;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceNumberService
{
    /**
     * Get the next sequential invoice number based on user settings.
     */
    public function getNextInvoiceNumber(User $user): string
    {
        return $this->getNextNumber($user, 'current_number', null);
    }

    /**
     * Get the next sequential credit note number (AV- prefix).
     */
    public function getNextCreditNoteNumber(User $user): string
    {
        return $this->getNextNumber($user, 'current_credit_note_number', 'AV');
    }

    /**
     * Preview the next number without incrementing.
     */
    public function previewNext(User $user): string
    {
        $settings = InvoiceNumber::firstOrNew(
            ['user_id' => $user->id],
            $this->getDefaultSettings()
        );

        if ($this->shouldReset($settings)) {
            return $this->formatNumber($settings, 1);
        }

        return $this->formatNumber($settings, $settings->current_number + 1);
    }

    /**
     * Format the invoice number based on settings.
     */
    public function formatNumber(InvoiceNumber $settings, int $number, ?string $prefixOverride = null): string
    {
        $parts = [];

        $prefix = $prefixOverride ?? $settings->prefix;
        if ($prefix) {
            $parts[] = $prefix;
        }

        if ($settings->include_year) {
            $parts[] = $settings->counter_year ?? Carbon::now()->year;
        }

        $parts[] = str_pad((string) $number, $settings->digit_count, '0', STR_PAD_LEFT);

        if (!$prefixOverride && $settings->suffix) {
            $parts[] = $settings->suffix;
        }

        return implode($settings->separator ?? '-', $parts);
    }

    /**
     * Shared logic for incrementing a counter and returning the formatted number.
     */
    private function getNextNumber(User $user, string $counterField, ?string $prefixOverride): string
    {
        return DB::transaction(function () use ($user, $counterField, $prefixOverride) {
            $settings = InvoiceNumber::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    $this->getDefaultSettings()
                );

            if ($this->shouldReset($settings)) {
                $settings->update([
                    $counterField => 0,
                    'counter_year' => Carbon::now()->year,
                ]);
            }

            $nextNumber = ($settings->$counterField ?? 0) + 1;

            $settings->update([
                $counterField => $nextNumber,
                'last_generated_at' => Carbon::now(),
            ]);

            return $this->formatNumber($settings, $nextNumber, $prefixOverride);
        });
    }

    private function shouldReset(InvoiceNumber $settings): bool
    {
        return $settings->reset_frequency === 'yearly' && $settings->counter_year !== Carbon::now()->year;
    }

    private function getDefaultSettings(): array
    {
        return [
            'prefix' => 'FAC',
            'digit_count' => 4,
            'reset_frequency' => 'yearly',
            'current_number' => 0,
            'current_credit_note_number' => 0,
            'counter_year' => Carbon::now()->year,
            'separator' => '-',
            'include_year' => true,
        ];
    }
}
