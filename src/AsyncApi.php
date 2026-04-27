<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi;

use Victormgomes\AsyncApi\Services\Docs\AsyncApiGenerator;

class AsyncApi
{
    public function __construct(
        protected AsyncApiGenerator $generator
    ) {}

    public function generate(?array $config = null): array
    {
        return $this->generator->generate();
    }
}
