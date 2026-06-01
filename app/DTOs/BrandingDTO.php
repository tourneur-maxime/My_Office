<?php

namespace App\DTOs;

class BrandingDTO
{
    public function __construct(
        public ?string $logoPath = null,
        public int $logoSize = 100,
        public string $logoPosition = 'left',
        public string $primaryColor = '#3B82F6',
        public string $secondaryColor = '#1E40AF',
        public string $fontFamily = 'sans-serif',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            logoPath: $data['logo_path'] ?? null,
            logoSize: (int) ($data['logo_size'] ?? 100),
            logoPosition: $data['logo_position'] ?? 'left',
            primaryColor: $data['primary_color'] ?? '#3B82F6',
            secondaryColor: $data['secondary_color'] ?? '#1E40AF',
            fontFamily: $data['font_family'] ?? 'sans-serif',
        );
    }

    public function toArray(): array
    {
        return [
            'logo_path' => $this->logoPath,
            'logo_size' => $this->logoSize,
            'logo_position' => $this->logoPosition,
            'primary_color' => $this->primaryColor,
            'secondary_color' => $this->secondaryColor,
            'font_family' => $this->fontFamily,
        ];
    }
}
