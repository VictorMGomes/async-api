# Usage

By default, the package automatically parses any class in your application that implements the `ShouldBroadcast` interface.

## Automatic Discovery

You don't need to add any attributes to your events. The package will automatically:

1. Extract the channel name from the `broadcastOn()` method.
2. Infer the operation name from the `broadcastAs()` method or the class name.
3. Use static analysis to extract the complete payload schema by reading properties, types, arrays, generics, and PHPDocs of the variables you broadcast in `broadcastWith()`.

```php
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserRegistered implements ShouldBroadcast
{
    public function __construct(
        public string $name,
        public int $age,
        /** @var array<string, \App\DTOs\Permission> */
        public array $permissions
    ) {}
    
    // ...
}
```

The example above automatically generates a full OpenAPI/AsyncAPI JSON Schema with `name` (string), `age` (integer), and `permissions` (an object mapping to the `Permission` DTO properties).

## Advanced Usage: Customizing with Attributes

While the package automatically discovers and documents your events, you might want to manually override the generated schema. To do this, add the `#[AsyncApi]` attribute to your event class:

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi(
    channel: 'custom.channel.{id}',
    description: 'This is a custom description for the event.',
    tags: ['users', 'auth']
)]
class UserRegistered implements ShouldBroadcast
{
    // ...
}
```

### Attribute Parameters

| Parameter       | Type      | Default  | Description                                                                     |
| --------------- | --------- | -------- | ------------------------------------------------------------------------------- |
| `channel`       | `?string` | `null`   | The broadcast channel URI. Supports dynamic parameters like `chat.{room}`.      |
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

## The `#[AsyncApiIgnore]` Attribute

If you have a broadcast event that you do not want to expose in your AsyncAPI documentation, simply add the `#[AsyncApiIgnore]` attribute:

```php
use Victormgomes\AsyncApi\Attributes\AsyncApiIgnore;

#[AsyncApiIgnore]
class InternalSystemEvent implements ShouldBroadcast
{
    // ...
}
```

## Action

The `action` parameter specifies the direction of the message flow:

- `'send'` (default): The application sends this message to the channel
- `'receive'`: The application receives this message from the channel
