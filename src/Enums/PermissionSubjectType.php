<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Enums;

enum PermissionSubjectType: string
{
    case User = 'user';
    case Role = 'role';
    case Everyone = 'everyone';
}
