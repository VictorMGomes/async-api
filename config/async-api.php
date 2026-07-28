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
    'info' => [
        'title' => env('APP_NAME', 'Laravel'),
        'version' => env('APP_VERSION', '1.0.0'),
        'description' => 'AsyncAPI documentation for the broadcasting API',
    ],

    /*
    |--------------------------------------------------------------------------
    | Servers (Environments)
    |--------------------------------------------------------------------------
    */
    'servers' => [
        // Representing the primary environment dynamically
        'default' => [
            // Matches REVERB_HOST and REVERB_PORT (fallback to localhost:8080 for client connection)
            'host' => env('REVERB_HOST', 'localhost').':'.env('REVERB_PORT', 8080),

            // Maps HTTP schemes to WebSocket protocols (https -> wss, http -> ws)
            'protocol' => env('REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws',

            'protocolVersion' => '1.3', // Standard for Pusher/Reverb
            'description' => 'Laravel Reverb Server (Pusher Protocol)',
            'security' => [
                ['$ref' => '#/components/securitySchemes/bearerAuth'],
            ],
            'bindings' => [
                'ws' => [
                    'method' => 'GET',
                    'query' => [
                        'type' => 'object',
                        'properties' => [
                            'appKey' => [
                                'type' => 'string',
                                'description' => 'The Reverb/Pusher App Key',
                                // Automatically pulls the app key into the docs as an example
                                'example' => env('REVERB_APP_KEY', 'your-app-key-here'),
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Components (Security and Reusable Schemas)
    |--------------------------------------------------------------------------
    */
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
                'description' => 'Enter your Sanctum token to authenticate with the broadcasting server.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to the AsyncAPI routes.
    |
    */
    'middleware' => [],
];
