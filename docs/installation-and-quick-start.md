# Installation & Quick Start

## Requirements

- PHP 8.3+
- Laravel 12.x or 13.x

## Install via Composer

```bash
composer require victormgomes/async-api
```

## Publish the Configuration

```bash
php artisan vendor:publish --tag="async-api-config"
```

This creates `config/async-api.php` where you can configure the AsyncAPI version, server details, security schemes, and more.

## Step 1: Annotate Your Events

Add the `#[AsyncApi]` attribute to any broadcast event class. The event must implement `ShouldBroadcast`.

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi(
    channel: 'chat.{room}',
    dto: \App\DTOs\ChatMessageDTO::class,
    description: 'A new chat message in a room',
    action: 'send',
)]
class ChatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

## Step 2: Generate the Specification

Run the artisan command to scan your event classes and generate the AsyncAPI spec:

```bash
php artisan docs:asyncapi
```

The specification will be written to `public/docs/asyncapi.json`.

## Step 3: Serve or Import

You can serve the generated JSON directly, or import it into tools like:

- [AsyncAPI Studio](https://studio.asyncapi.com/) for visual editing
- [Redocly](https://redocly.com/) for interactive documentation
- Any AsyncAPI-compatible code generator

---

## Next Steps

- Learn more about the `#[AsyncApi]` attribute options in [Usage](/usage).
- Configure servers, security, and more in [Configuration](/configuration).
