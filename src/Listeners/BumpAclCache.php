<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Listeners;

use Kurt\Modules\Core\Support\ModuleCacheFactory;

/**
 * Invalidates the whole ACL cache keyspace on any permission or move change.
 *
 * Wired to FolderPermissionChanged and FolderMoved in the service provider. A
 * single generational bump orphans every cached capability under the `acl`
 * scope in O(1) — no subtree enumeration — so a revoked grant or a moved
 * subtree can never be served from a stale entry. Global by design (the
 * accepted tradeoff): one change cools the entire ACL cache, always the safe
 * direction.
 */
final class BumpAclCache
{
    public function __construct(private readonly ModuleCacheFactory $factory) {}

    public function handle(object $event): void
    {
        $this->factory->generationalFor('resource-library')->bump('acl');
    }
}
