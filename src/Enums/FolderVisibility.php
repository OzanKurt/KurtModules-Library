<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Enums;

enum FolderVisibility: string
{
    case Public = 'public';
    case Restricted = 'restricted';
    case Private = 'private';
}
