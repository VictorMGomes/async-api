<?php

declare(strict_types=1);

namespace Victormgomes\AsyncApi\Enums;

enum Action: string
{
    case Send = 'send';
    case Receive = 'receive';
}
