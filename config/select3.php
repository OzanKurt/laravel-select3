<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Defaults applied to every <x-select3> / Select3::make() builder.
    |--------------------------------------------------------------------------
    | These flow into the data-s3-config JSON the JS enhancer reads. Any value
    | set explicitly on a builder overrides the matching default here.
    */

    // 'bootstrap5' | 'tailwind' | null (use the base theme).
    'theme' => null,

    // A select3 i18n pack code ('en', 'tr', ...) or null for the default.
    'locale' => null,

    // Show the search box.
    'searchable' => true,

    // Debounce (ms) for AJAX search requests.
    'debounce' => 250,

    // Minimum characters before an AJAX search fires.
    'min_chars' => 1,

    // Default page size for the AJAX search responder.
    'per_page' => 20,
];
