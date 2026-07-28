<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AsyncAPI Version
    |--------------------------------------------------------------------------
    */
    'asyncapi_version' => '3.0.0',

    /*
    |--------------------------------------------------------------------------
    | Default Content Type
    |--------------------------------------------------------------------------
    */
    'default_content_type' => 'application/json',

    /*
    |--------------------------------------------------------------------------
    | Info (API Metadata)
    |--------------------------------------------------------------------------
    */
    'info_title' => env('APP_NAME', 'Laravel Broadcasting API'),
    'info_version' => env('APP_VERSION', '1.0.0'),
    'info_description' => 'AsyncAPI documentation for the broadcasting API',

    /*
    |--------------------------------------------------------------------------
    | Server Configuration (Reverb / Pusher)
    |--------------------------------------------------------------------------
    */
    'server_host' => env('REVERB_HOST', 'localhost'),
    'server_port' => env('REVERB_PORT', 8080),
    'server_scheme' => env('REVERB_SCHEME', 'https'),
    'server_app_key' => env('REVERB_APP_KEY', 'your-app-key-here'),
    'server_description' => 'Laravel Reverb Server (Pusher Protocol)',

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security_description' => 'Enter your Sanctum token to authenticate with the broadcasting server.',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to the AsyncAPI routes.
    |
    */
    'middleware' => [],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, detailed information about the scanning and schema
    | generation process will be logged via Log::info().
    |
    */
    'debug' => env('APP_DEBUG', true),
];
