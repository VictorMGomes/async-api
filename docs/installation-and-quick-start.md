# Installation & Quick Start

## Requirements

- PHP 8.3+
- Laravel 12.x or 13.x

## Quick Start

### Step 1: Installation

Install the package via Composer:

```bash
composer require victormgomes/async-api
```

### Step 2: Publish the Configuration (Optional)

If you need to customize the default settings, publish the configuration file:

```bash
php artisan vendor:publish --tag="async-api-config"
```

### Step 3: Access the Documentation

Visit `http://localhost/docs/broadcast` in your application to view the interactive AsyncAPI
documentation powered by the default
[AsyncAPI Studio](https://www.asyncapi.com/) viewer. The raw JSON
specification is also available at `http://localhost/docs/broadcast/json`.

---

## Next Steps

- Learn more about the `#[AsyncApi]` attribute options in [Usage](/usage).
- Configure servers, security, and more in [Configuration](/configuration).
