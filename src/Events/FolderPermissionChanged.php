<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;

final class FolderPermissionChanged
{
    use Dispatchable;

    public function __construct(public readonly FolderPermission $permission) {}
}
