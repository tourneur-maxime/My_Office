<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | This value is the version number of the application. It is displayed
    | in the application footer and on the about page.
    |
    */

    'app_version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Factur-X Specification Version
    |--------------------------------------------------------------------------
    |
    | This value represents the version of the Factur-X specification that
    | the application implements. It should be updated when upgrading to
    | a newer version of the specification.
    |
    */

    'facturx_spec_version' => '1.0.05',

    /*
    |--------------------------------------------------------------------------
    | CII D16B Version
    |--------------------------------------------------------------------------
    |
    | The UN/CEFACT Cross Industry Invoice D16B standard version used.
    |
    */

    'cii_version' => 'D16B',
];
