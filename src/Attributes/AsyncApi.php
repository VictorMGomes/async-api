<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Attributes;

use Attribute;
use Victormgomes\AsyncApi\Enums\Action;

#[Attribute(Attribute::TARGET_CLASS)]
class AsyncApi
{
    public function __construct(
        public ?string $channel = null,
        public ?string $dto = null,
        public string $description = '',
        public ?string $name = null,
        public ?string $summary = null,
        public ?string $operationId = null,
        public Action $action = Action::Send,
        /** @var string[] */
        public array $tags = [],
        /** @var array<mixed> */
        public array $examples = [],
        /** @var array<string, mixed> */
        public array $bindings = [],
        /** @var array{url: string, description?: string}|null */
        public ?array $externalDocs = null,
        public ?string $correlationId = null,
        /** @var array<string|array<string, mixed>>|null $security Custom security schemes for this specific operation */
        public ?array $security = null,
    ) {}
}
