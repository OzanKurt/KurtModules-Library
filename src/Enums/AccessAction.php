<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Enums;

enum AccessAction: string
{
    case View = 'view';
    case Download = 'download';
}
