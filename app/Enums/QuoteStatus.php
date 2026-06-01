<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Brouillon = 'Brouillon';
    case Envoyé = 'Envoyé';
    case Approuvé = 'Approuvé';
    case Rejeté = 'Rejeté';
    case Expiré = 'Expiré';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoyé => 'Envoyé',
            self::Approuvé => 'Approuvé',
            self::Rejeté => 'Rejeté',
            self::Expiré => 'Expiré',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
