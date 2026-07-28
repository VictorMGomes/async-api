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
    'info_title' => env('APP_NAME', 'Laravel Broadcasting API'),
    'info_version' => env('APP_VERSION', '1.0.0'),
    'info_description' => 'AsyncAPI documentation for the broadcasting API',

    /*
    | Server Configuration (Reverb / Pusher)
    */
    'server_host' => env('REVERB_HOST', 'localhost'),
    'server_port' => env('REVERB_PORT', 8080),
    'server_scheme' => env('REVERB_SCHEME', 'https'),
    'server_app_key' => env('REVERB_APP_KEY', 'your-app-key-here'),
    'server_description' => 'Laravel Reverb Server (Pusher Protocol)',

    /*
    | Security
    */
    'security_description' => 'Enter your Sanctum token to authenticate with the broadcasting server.',

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
| `info_title`                 | The title of your API.                                       |
| `info_version`               | The version of your API.                                     |
| `info_description`           | A short description of your API.                             |
| `server_host`                | The broadcasting server host (e.g. `localhost`).             |
| `server_port`                | The broadcasting server port (e.g. `8080`).                  |
| `server_scheme`              | The HTTP scheme used to connect (e.g. `https` or `http`).    |
| `server_app_key`             | The Reverb/Pusher app key.                                   |
| `security_description`       | The description of the Bearer authentication method.         |
| `middleware`                 | Middleware to assign to the AsyncAPI routes.                 |

## Debug Mode

When `config('async-api.debug')` is `true`, the package logs detailed information about the scanning and generation process via `Log::info()`. This is useful during development to see which events were discovered and how they were processed.
