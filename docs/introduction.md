# Introduction

In modern event-driven architectures, documenting WebSocket interfaces is as crucial as documenting REST APIs. **Laravel Async API** bridges this gap for Laravel applications by automating the generation of AsyncAPI specifications.

Stop maintaining manual AsyncAPI files. Document your events directly in your PHP code with a simple attribute, and let the package generate a fully compliant AsyncAPI 3.0 specification.

## Features

- **Zero-Effort Documentation**: Stop maintaining manual AsyncAPI files. Document your events directly in your PHP code.
- **Attribute-Based**: Uses modern PHP 8 attributes for a clean and declarative developer experience.
- **Schema Integration**: Automatically extracts payload schemas from DTOs or models, ensuring your documentation always matches your code.
- **Seamless Integration**: Works perfectly with Laravel's broadcasting system (Reverb, Pusher, Soketi).
- **AsyncAPI 3.0 Compliant**: Generates specifications that follow the latest AsyncAPI standard.
- **AI Agent Ready**: Includes an official [Laravel Boost](https://github.com/laravel/boost) skill to automatically teach AI agents how to use this package in your project.

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

The package discovers all `#[AsyncApi]`-attributed events that implement `ShouldBroadcast`, extracts their schemas, and compiles them into a standardized AsyncAPI 3.0 JSON specification.

```bash
php artisan docs:asyncapi
```

The generated file is written to `public/docs/asyncapi.json` and can be served directly or imported into any AsyncAPI-compatible tool.
