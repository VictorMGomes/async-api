# Laravel Async API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/victormgomes/async-api/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/victormgomes/async-api/actions?query=workflow%3Astyle-check+branch%3Amain)
[![GitHub Code Quality Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=code%20quality&style=flat-square)](https://github.com/victormgomes/async-api/actions?query=workflow%3Acode-quality+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![License](https://img.shields.io/packagist/l/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)

**Automatically generate AsyncAPI 3.0 documentation from your Laravel broadcast events using PHP attributes.**

---

## Why use this package?

In modern event-driven architectures, documenting WebSocket interfaces is as crucial as documenting REST APIs. **Laravel Async API** bridges this gap for Laravel applications by automating the generation of AsyncAPI specifications.

- **Zero-Effort Documentation**: Stop maintaining manual AsyncAPI files. Document your events directly in your PHP code.
- **Attribute-Based**: Uses modern PHP 8 attributes for a clean and declarative developer experience.
- **Schema Integration**: Automatically extracts payload schemas from DTOs or models, ensuring your documentation always matches your code.
- **Seamless Integration**: Works perfectly with Laravel's broadcasting system (Reverb, Pusher, Soketi).
- **AsyncAPI 3.0 Compliant**: Generates specifications that follow the latest AsyncAPI standard.

---

## How It Works

Add the `#[AsyncApi]` attribute to your broadcast event classes, optionally specifying a DTO for automatic schema extraction:

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi(dto: ChatMessageDTO::class, channel: 'chat.{room}')]
class ChatMessage implements ShouldBroadcast
{
    // ...
}
```

Run the artisan command to generate the specification:

```bash
php artisan docs:asyncapi
```

The package discovers all `#[AsyncApi]`-attributed events, extracts their schemas, and compiles them into a fully compliant AsyncAPI 3.0 JSON specification at `public/docs/asyncapi.json`.

---

## Installation

```bash
composer require victormgomes/async-api
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="async-api-config"
```

---

## Quick Start

### Step 1: Annotate Your Event

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi(
    channel: 'chat.{room}',
    dto: \App\DTOs\ChatMessageDTO::class,
    description: 'A new chat message in a room',
    action: 'send',
)]
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

### Step 2: Generate the Specification

```bash
php artisan docs:asyncapi
```

### Step 3: Use It

Import `public/docs/asyncapi.json` into [AsyncAPI Studio](https://studio.asyncapi.com/), [Redocly](https://redocly.com/), or any AsyncAPI-compatible tool.

---

## Documentation

For full documentation, visit **[laravel-async-api.victormgomes.net](https://laravel-async-api.victormgomes.net/)**.

---

## Testing

```bash
composer test
```

---

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Victor M. Gomes](https://github.com/VictorMGomes)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
