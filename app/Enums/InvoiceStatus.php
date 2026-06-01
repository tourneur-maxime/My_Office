<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Brouillon = 'Brouillon';
    case Envoyé = 'Envoyé';
    case Payé = 'Payé';
    case PartiellementPayé = 'Partiellement Payé';
    case EnRetard = 'En Retard';
    case Annulé = 'Annulé';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Envoyé => 'Envoyé',
            self::Payé => 'Payé',
            self::PartiellementPayé => 'Partiellement Payé',
            self::EnRetard => 'En Retard',
            self::Annulé => 'Annulé',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
