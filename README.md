# Laravel Async API

Automatically generates AsyncAPI 3.0 documentation from your Laravel broadcast
events using PHP attributes.

## Package Status

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![Total Downloads](https://img.shields.io/packagist/dt/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![License](https://img.shields.io/packagist/l/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)

[![PHP Versions](https://img.shields.io/badge/PHP-8.3_|_8.4_|_8.5-777BB4.svg?style=flat-square&logo=php)](https://php.net/)
[![Laravel Versions](https://img.shields.io/badge/Laravel-12.x_|_13.x-22C55E.svg?style=flat-square&logo=laravel)](https://laravel.com/)

[![Tests Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/victormgomes/async-api/actions/workflows/ci.yml)
[![Code Style Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/victormgomes/async-api/actions/workflows/ci.yml)
[![Code Quality Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/ci.yml?branch=main&label=PHPStan%20%26%20Insights&style=flat-square)](https://github.com/victormgomes/async-api/actions/workflows/ci.yml)

## Why Use It?

In modern event-driven architectures, documenting broadcast events is as
crucial as documenting REST APIs. `laravel-async-api` bridges this gap for
Laravel applications by automating the generation of AsyncAPI specifications.

It empowers you to document your broadcast events directly in your PHP code
using modern PHP 8 attributes, and generates fully compliant AsyncAPI 3.0
specifications with zero manual effort.

## Features

- **Zero-Effort Documentation:** Stop maintaining manual AsyncAPI files. Document your events directly in your PHP code.
- **Attribute-Based:** Uses modern PHP 8 attributes for a clean and declarative developer experience.
- **Schema Integration:** Automatically extracts payload schemas from DTOs or models, ensuring your documentation always matches your code.
- **Seamless Integration:** Works perfectly with Laravel's broadcasting system (Reverb, Pusher, Soketi).
- **AsyncAPI 3.0 Compliant:** Generates specifications that follow the latest AsyncAPI standard.
- **AI Agent Ready:** Includes an official [Laravel Boost](https://github.com/laravel/boost) skill to automatically teach AI agents (like Claude Code and Cursor) how to use this package in your project.

## How It Works

Add the `#[AsyncApi]` attribute to your broadcast event classes:

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi]
class ChatMessage implements ShouldBroadcast
{
    // ...
}
```

The package automatically discovers your attributed events and registers a
route that serves the AsyncAPI 3.0 JSON specification.

---

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
documentation. The raw JSON specification is also available at `/docs/ws/json`.

---

## Documentation

For a deep dive into the features, please read the
[Official Documentation](https://laravel-async-api.victormgomes.net/).

---

## Credits

- [Victor M. Gomes](https://github.com/victormgomes)
- [All Contributors](https://github.com/victormgomes/async-api/graphs/contributors)

## Support Us

If you find this package useful in your day-to-day development, please consider
[sponsoring my work](https://github.com/sponsors/VictorMGomes) or leaving a ⭐
on the repository. Your support directly helps keep this project actively
maintained and free!

---

## Community & Guidelines

- [Upgrading Guide](https://laravel-async-api.victormgomes.net/UPGRADING)
- [Changelog](https://github.com/victormgomes/async-api/releases)
- [Contributing](https://github.com/victormgomes/async-api/blob/main/.github/CONTRIBUTING.md)
- [Code of Conduct](https://github.com/victormgomes/async-api/blob/main/.github/CODE_OF_CONDUCT.md)
- [Security Policy](https://github.com/victormgomes/async-api/blob/main/.github/SECURITY.md)
- [Support & Help](https://github.com/victormgomes/async-api/blob/main/.github/SUPPORT.md)

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
