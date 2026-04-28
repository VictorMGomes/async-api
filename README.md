# Async API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/victormgomes/async-api/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/victormgomes/async-api/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/victormgomes/async-api/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)
[![License](https://img.shields.io/packagist/l/victormgomes/async-api.svg?style=flat-square)](https://packagist.org/packages/victormgomes/async-api)

**Automatically generate documentation for the AsyncAPI specification based on Laravel events**

---

## Introduction

In modern event-driven architectures, documenting WebSocket interfaces is as crucial as documenting REST APIs. **Async-API** bridges this gap for Laravel applications by automating the generation of AsyncAPI specifications.

### Why use this package?

*   **Zero-Effort Documentation**: Stop maintaining manual AsyncAPI files. Document your events directly in your PHP code.
*   **Attribute-Based**: Uses modern PHP 8 attributes for a clean and declarative developer experience.
*   **Schema Integration**: Automatically extracts payload schemas from DTOs or models, ensuring your documentation always matches your code.
*   **Seamless Integration**: Works perfectly with Laravel's broadcasting system.

---

## Support us

We invest a lot of resources into creating [best in class open source packages](https://github.com/victormgomes). You can support us by [sponsoring us on GitHub](https://github.com/sponsors/VictorMGomes).

---

## Installation

You can install the package via composer:

```bash
composer require victormgomes/async-api
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="async-api-config"
```

---

## Usage

Simply add the `#[AsyncApi]` attribute to your event classes. You can specify a DTO class to automatically generate the schema for the message payload.

```php
use Victormgomes\AsyncApi\Attributes\AsyncApi;

#[AsyncApi(dto: ChatPresenceDTO::class)]
class ChatPresence implements ShouldBroadcast
{
    // ...
}
```

The package will then discover these attributes and compile them into a standardized AsyncAPI specification.

---

## Testing

```bash
composer test
```

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
