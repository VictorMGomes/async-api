<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsyncApi
{
    public function __construct(
        public ?string $channel = null,
        public ?string $dto = null,
        public string $description = '',
        public ?string $name = null,
        // AsyncAPI 3.0 Standard Properties
        public ?string $summary = null,
        public ?string $operationId = null,
        public string $action = 'send', // 'send' or 'receive'
        public array $tags = [],
        public array $examples = [],
        public array $bindings = [],
        public ?array $externalDocs = null,
        // Advanced 3.0 Properties
        public ?string $correlationId = null,
        public ?array $security = null, // Custom security for this specific operation
    ) {}
}
