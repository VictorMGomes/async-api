# Installation & Quick Start

## Requirements

- PHP 8.3+
- Laravel 12.x or 13.x

## Installation

1. Install the package via Composer:

```bash
composer require victormgomes/async-api
```

1. Publish the configuration file:

```bash
php artisan vendor:publish --tag="async-api-config"
```

---

## Quick Start

### Step 1: Annotate Your Event

Add the `#[AsyncApi]` attribute to any broadcast event class. The event must
implement `ShouldBroadcast`.

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi]
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

### Step 2: Access the Documentation

Visit `/docs/ws` in your application to view the interactive AsyncAPI
documentation powered by the default
[AsyncAPI Studio](https://www.asyncapi.com/) viewer. The raw JSON
specification is also available at `/docs/ws/json`.

---

## Next Steps

- Learn more about the `#[AsyncApi]` attribute options in [Usage](/usage).
- Configure servers, security, and more in [Configuration](/configuration).
