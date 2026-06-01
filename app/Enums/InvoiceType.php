<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Facture = 'facture';
    case Avoir = 'avoir';

    public function label(): string
    {
        return match ($this) {
            self::Facture => 'Facture',
            self::Avoir => 'Avoir',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Facture => 'FA',
            self::Avoir => 'AV',
        };
    }
}
