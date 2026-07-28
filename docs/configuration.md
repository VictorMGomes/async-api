# Configuration

After publishing the configuration file (`php artisan vendor:publish --tag="async-api-config"`), you can customize the generated AsyncAPI specification.

## Configuration Reference

```php
// config/async-api.php

return [
    /*
    | AsyncAPI Version
    */
    'asyncapi_version' => '3.0.0',

    /*
    | Default Content Type
    */
    'default_content_type' => 'application/json',

    /*
    | Info (API Metadata)
    */
    'info' => [
        'title' => env('APP_NAME', 'Laravel'),
        'version' => env('APP_VERSION', '1.0.0'),
        'description' => 'AsyncAPI documentation for the WebSocket API',
    ],

    /*
    | Servers (Environments)
    */
    'servers' => [
        'default' => [
            'host' => env('REVERB_HOST', 'localhost') . ':' . env('REVERB_PORT', 8080),
            'protocol' => env('REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws',
            'protocolVersion' => '1.3',
            'description' => 'Laravel Reverb Server (Pusher Protocol)',
            // ...
        ],
    ],

    /*
    | Components (Security and Reusable Schemas)
    */
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ],
    ],

    /*
    | Route Middleware
    */
    'middleware' => [],
];
```

## Options

| Option                       | Description                                                  |
| ---------------------------- | ------------------------------------------------------------ |
| `asyncapi_version`           | The AsyncAPI specification version (default: `3.0.0`).       |
| `default_content_type`       | Default content type for all messages.                       |
| `info`                       | API metadata: title, version, description, contact, license. |
| `servers`                    | Server configurations (host, protocol, security).            |
| `components.securitySchemes` | Reusable security scheme definitions.                        |
| `components.schemas`         | Additional reusable schemas (auto-generated from DTOs).      |
| `middleware`                 | Middleware to assign to the AsyncAPI routes.                 |

## Debug Mode

When `config('async-api.debug')` is `true`, the package logs detailed information about the scanning and generation process via `Log::info()`. This is useful during development to see which events were discovered and how they were processed.
