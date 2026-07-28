<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Victormgomes\AsyncApi\AsyncApi
 */
class AsyncApi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Victormgomes\AsyncApi\AsyncApi::class;
    }
}
