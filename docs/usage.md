# The `#[AsyncApi]` Attribute

The `#[AsyncApi]` PHP attribute is the core of the package. Add it to any Laravel broadcast event class to include it in the generated AsyncAPI specification.

## Basic Usage

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi]
class UserRegistered implements ShouldBroadcast
{
    // ...
}
```

With no parameters, the package will infer the channel name from the event's `broadcastOn()` method and use the event class name as the operation name.

## Attribute Parameters

| Parameter       | Type      | Default  | Description                                                                     |
| --------------- | --------- | -------- | ------------------------------------------------------------------------------- |
| `channel`       | `?string` | `null`   | The broadcast channel URI. Supports dynamic parameters like `chat.{room}`.      |
| `dto`           | `?string` | `null`   | Fully qualified class name of a DTO/Model to extract the payload schema from.   |
| `description`   | `string`  | `''`     | Human-readable description of the operation.                                    |
| `name`          | `?string` | `null`   | Custom name for the event. Defaults to the class short name or `broadcastAs()`. |
| `summary`       | `?string` | `null`   | Short summary of the operation (AsyncAPI 3.0).                                  |
| `operationId`   | `?string` | `null`   | Unique identifier for the operation. Defaults to `{action}{eventName}`.         |
| `action`        | `string`  | `'send'` | The operation action: `'send'` or `'receive'`.                                  |
| `tags`          | `array`   | `[]`     | Tags for grouping operations in the specification.                              |
| `examples`      | `array`   | `[]`     | Example payloads for the message.                                               |
| `bindings`      | `array`   | `[]`     | Protocol-specific bindings (e.g., WebSocket configuration).                     |
| `externalDocs`  | `?array`  | `null`   | External documentation link.                                                    |
| `correlationId` | `?string` | `null`   | Correlation ID location for message tracing.                                    |
| `security`      | `?array`  | `null`   | Custom security schemes for this specific operation.                            |

## DTO Schema Extraction

When you specify a `dto`, the package uses reflection to extract all public properties and their types, generating a JSON Schema for the message payload.

```php
#[AsyncApi(dto: ChatMessageDTO::class)]
class ChatMessage implements ShouldBroadcast { /* ... */ }
```

The `ChatMessageDTO` class should be a simple class with typed public properties (or extend `Spatie\LaravelData\Data`):

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

class ChatMessageDTO
{
    public function __construct(
        public string $message,
        public string $sender,
        public ?string $timestamp = null,
    ) {}
}
```

If no `dto` is specified, the package attempts to auto-discover the payload by:

1. Looking for a constructor parameter named `$data`
2. Checking for `Spatie\LaravelData\Data` subclasses
3. Guessing DTO class names based on naming conventions (`{Event}DTO`, `{Event}EventDTO`)

## Channel Inference

If `channel` is not specified, the package reads the event source file and attempts to extract the channel from `new Channel(...)`, `new PrivateChannel(...)`, or `new PresenceChannel(...)` calls in the `broadcastOn()` method.

Dynamic channel parameters (e.g., `chat.{room}`) are automatically detected and documented as AsyncAPI channel parameters.

## Action

The `action` parameter specifies the direction of the message flow:

- `'send'` (default): The application sends this message to the channel
- `'receive'`: The application receives this message from the channel
