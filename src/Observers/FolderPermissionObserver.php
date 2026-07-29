<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Observers;

use Kurt\Modules\ResourceLibrary\Events\FolderPermissionChanged;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;

/**
 * Fires {@see FolderPermissionChanged} whenever a grant row is created, updated
 * or removed, whatever the path (REST API, Filament relation manager, domain
 * code). That event drives the ACL cache bump, so every real permission change
 * invalidates the cross-request capability cache — without this, a revoked grant
 * would linger in cache until its TTL, i.e. a stale-grant security hole.
 */
final class FolderPermissionObserver
{
    public function saved(FolderPermission $permission): void
    {
        FolderPermissionChanged::dispatch($permission);
    }

    public function deleted(FolderPermission $permission): void
    {
        FolderPermissionChanged::dispatch($permission);
    }
}
