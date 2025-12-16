<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Translation Cache TTL
    |--------------------------------------------------------------------------
    |
    | The time in seconds that translations should be cached.
    |
    */
    'cache_ttl' => env('TRANSLATION_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | CDN Configuration
    |--------------------------------------------------------------------------
    |
    | Enable CDN support for translation exports.
    |
    */
    'cdn_enabled' => env('CDN_ENABLED', false),
    'cdn_url' => env('CDN_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of supported locales for translations.
    |
    */
    'supported_locales' => [
        'en' => 'English',
        'fr' => 'French',
        'es' => 'Spanish',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
    ],
];
