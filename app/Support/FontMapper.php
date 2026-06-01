<?php

namespace App\Support;

class FontMapper
{
    private const MAP = [
        'monospace' => 'dejavusansmono',
        'sans-serif' => 'dejavusans',
        'arial' => 'dejavusans',
        'verdana' => 'dejavusans',
        'georgia' => 'dejavuserif',
        'serif' => 'dejavuserif',
    ];

    public static function resolve(string $userFont): string
    {
        $lower = strtolower($userFont);

        foreach (self::MAP as $key => $value) {
            if (str_contains($lower, $key)) {
                return $value;
            }
        }

        return 'dejavusans';
    }
}
