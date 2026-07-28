---
name: laravel-async-api
description: Automatically generate AsyncAPI 3.0 documentation from Laravel broadcast events using PHP attributes.
---

# laravel-async-api Skill

## Overview

Automatically generate [AsyncAPI 3.0](https://www.asyncapi.com/) specifications from your Laravel broadcast events. The package uses Laravel Surveyor (Abstract Syntax Tree analysis) to infer channels, operation names, and complex payloads (DTOs, arrays, PHPDoc properties) directly from your `ShouldBroadcast` event classes automatically.

Integrates seamlessly with Laravel's broadcasting system (Reverb, Pusher, Soketi).

## 🚀 Quick Start

By default, any event that implements `ShouldBroadcast` is automatically documented! You don't need to add any attributes.

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\Channel;

class ChatMessage implements ShouldBroadcast
{
    public function __construct(
        public string $room,
        public string $message,
        public string $sender,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('chat.'.$this->room)];
    }
}
```

To override defaults or hide an event, use the optional `#[AsyncApi]` or `#[AsyncApiIgnore]` attributes.

### 2. Generate the Specification

Run the artisan command to scan all attributed events and produce the AsyncAPI JSON spec:

```bash
php artisan docs:asyncapi
```

The file is written to `public/docs/asyncapi.json`.

## `#[AsyncApi]` Attribute Parameters

| Parameter     | Type      | Default  | Description                                                    |
| ------------- | --------- | -------- | -------------------------------------------------------------- |
| `channel`     | `?string` | `null`   | Broadcast channel URI (supports `{param}` placeholders).       |
| `dto`         | `?string` | `null`   | DTO/Model class for payload schema extraction.                 |
| `description` | `string`  | `''`     | Human-readable operation description.                          |
| `name`        | `?string` | `null`   | Custom event name (defaults to class name or `broadcastAs()`). |
| `summary`     | `?string` | `null`   | Short summary (AsyncAPI 3.0).                                  |
| `operationId` | `?string` | `null`   | Unique operation identifier.                                   |
| `action`      | `string`  | `'send'` | `'send'` or `'receive'`.                                       |
| `tags`        | `array`   | `[]`     | Tags for grouping operations.                                  |
| `security`    | `?array`  | `null`   | Per-operation security overrides.                              |

## DTO Schema Extraction

The package reflects on all public properties of the DTO class to generate a JSON Schema. Supported DTO patterns:

- Plain PHP classes with typed public properties
- `Spatie\LaravelData\Data` subclasses

If no `dto` is specified, the package auto-discovers the payload by inspecting constructor parameters and naming conventions (`*DTO`, `*EventDTO`).

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="async-api-config"
```

Key configuration options in `config/async-api.php`:

- `asyncapi_version` — Specification version (default: `3.0.0`)
- `info` — API metadata (title, version, description)
- `servers` — Server configurations (host, protocol, security)
- `components.securitySchemes` — Reusable security definitions
- `debug` — Enable detailed logging during generation

## Advanced Usage & Documentation

For full attribute reference, channel inference logic, and configuration details, consult the official documentation:

- **Docs:** `vendor/victormgomes/async-api/docs/`
- **Source code:** `vendor/victormgomes/async-api/src/`
