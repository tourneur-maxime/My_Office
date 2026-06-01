<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Branding Settings
    |--------------------------------------------------------------------------
    |
    | These are the default values for branding settings that will be applied
    | when a user resets their template to defaults.
    |
    */

    'defaults' => [
        'logo_path' => null,
        'logo_size' => 100,
        'logo_position' => 'left',
        'primary_color' => '#3B82F6', // Tailwind blue-500
        'secondary_color' => '#1E40AF', // Tailwind blue-800
        'font_family' => 'sans-serif',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Fonts
    |--------------------------------------------------------------------------
    |
    | List of fonts that are available for PDF generation.
    | Only embeddable fonts compatible with PDF/A-3 are included.
    |
    */

    'fonts' => [
        'sans-serif' => 'Sans Serif (par defaut)',
        'serif' => 'Serif',
        'monospace' => 'Monospace',
        'Arial, sans-serif' => 'Arial',
        'Georgia, serif' => 'Georgia',
        'Verdana, sans-serif' => 'Verdana',
        'dejavusans' => 'DejaVu Sans',
    ],
];
