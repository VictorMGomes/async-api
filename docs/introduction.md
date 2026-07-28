# Introduction

In modern event-driven architectures, documenting broadcast events is as
crucial as documenting REST APIs. `laravel-async-api` bridges this gap for
Laravel applications by automating the generation of AsyncAPI specifications.

Stop maintaining manual AsyncAPI files. Your events are documented automatically,
and the package generates a fully compliant AsyncAPI 3.0 specification.

## Features

- **Zero-Effort Documentation:** Stop maintaining manual AsyncAPI files. Your events are documented automatically.
- **Schema Integration:** The broadcasting implementation structure, including data types, is inferred automatically to ensure your documentation always matches your code.
- **Seamless Integration:** Works perfectly with Laravel's broadcasting system (Reverb, Pusher, Soketi).
- **AsyncAPI 3.0 Compliant:** Generates specifications that follow the latest AsyncAPI standard.
- **AI Agent Ready:** Includes an official [Laravel Boost](https://github.com/laravel/boost) skill to automatically teach AI agents (like Claude Code and Cursor) how to use this package in your project.

## How It Works

The package automatically discovers any event class in your application that implements `ShouldBroadcast`.

```php
class ChatMessage implements ShouldBroadcast
{
    // ...
}
```

By simply implementing `ShouldBroadcast`, your event is instantly documented. The package uses static code analysis to automatically infer the payload schema directly from your properties, arrays, generics, and PHPDocs without requiring any manual reflection or hints!

If you want to customize the generated schema (like overriding the channel name or adding descriptions), you can optionally use the `#[AsyncApi]` attribute. If you want to hide an event, use `#[AsyncApiIgnore]`.

The package exposes a route that serves the AsyncAPI 3.0 JSON specification. Visit `/docs/broadcast` in your application to view the interactive documentation UI.
