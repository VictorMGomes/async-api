---
layout: home

hero:
    name: Laravel Async API
    text: AsyncAPI documentation for Laravel Broadcasting.
    tagline: Automatically generate AsyncAPI 3.0 specifications from your Laravel broadcasting implementations.
    actions:
        - theme: brand
          text: Get Started
          link: /installation-and-quick-start
        - theme: alt
          text: View on GitHub
          link: https://github.com/victormgomes/async-api
        - theme: alt
          text: Packagist
          link: https://packagist.org/packages/victormgomes/async-api

features:
    - title: Zero-Effort Documentation
      details: Stop maintaining manual AsyncAPI files. Document your events automatically using static code analysis.
    - title: 🔗 Schema Integration
      details: Automatically extracts payload schemas from DTOs or models, ensuring your documentation always matches your code.
---

# 🚀 Quick Start

### 1. Install the package

```bash
composer require victormgomes/async-api
```

### 2. Access the documentation

Visit `http://localhost/docs/broadcast` in your browser. All your `ShouldBroadcast` events are already documented!
