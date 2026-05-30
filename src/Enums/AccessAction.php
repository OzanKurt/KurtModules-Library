<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Enums;

enum AccessAction: string
{
    case View = 'view';
    case Download = 'download';
}
