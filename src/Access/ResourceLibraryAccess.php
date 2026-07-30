<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Core\Support\GenerationalModuleCache;
use Kurt\Modules\Core\Support\ModuleCacheFactory;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Throwable;

/**
 * Two-layer capability resolution in front of {@see PermissionResolver}.
 *
 * L1 = a per-request memo (this instance is request-scoped): the same
 * (subject, folder) is resolved at most once per request.
 *
 * L2 = a cross-request generational cache ({@see GenerationalModuleCache} under
 * the `acl` scope). Every capability-affecting input is either in the key
 * (subjectId, rolesHash, folderId) or in the scope's generation, which is
 * bumped on FolderPermissionChanged / FolderMoved. Only the capability RESULT
 * is cached, never a policy decision — `check()` re-derives the yes/no each time.
 *
 * SECURITY: the cache is fail-safe. When it is disabled (config
 * `resource-library.cache.enabled=false`, handled by the underlying
 * ModuleCache) or throws for any reason, resolution falls through to the LIVE
 * resolver. A cache fault can only cost performance, never grant a revoked
 * capability — the module never fails OPEN.
 */
final class ResourceLibraryAccess
{
    /**
     * L1 per-request memo, keyed by "{subjectId}:{folderId}".
     *
     * @var array<string, ?Capability>
     */
    private array $memo = [];

    private ?GenerationalModuleCache $cache = null;

    public function __construct(
        private readonly PermissionResolver $resolver,
        ?GenerationalModuleCache $cache = null,
    ) {
        $this->cache = $cache;
    }

    public function check(?Authenticatable $user, Folder|Item $target, Capability $needed): bool
    {
        $folder = $target instanceof Item ? $target->folder : $target;
        $memoKey = sprintf('%s:%d', $user?->getAuthIdentifier() ?? 'guest', $folder->id);

        if (! array_key_exists($memoKey, $this->memo)) {
            $this->memo[$memoKey] = $this->resolveCapability($user, $folder);
        }

        $best = $this->memo[$memoKey];

        return $best !== null && $best->rank() >= $needed->rank();
    }

    public function flush(): void
    {
        $this->memo = [];
    }

    /**
     * L2 lookup around the live resolver. The key carries every input that can
     * change the answer without a bump signal (subject identity + role
     * fingerprint); permission and move changes are covered by the scope
     * generation. Any cache error falls through to the live resolver.
     */
    private function resolveCapability(?Authenticatable $user, Folder $folder): ?Capability
    {
        $live = fn (): ?Capability => $this->resolver->highestCapability($user, $folder);

        $key = sprintf(
            'subject:%s:roles:%s:folder:%d',
            $user?->getAuthIdentifier() ?? 'guest',
            RoleFingerprint::for($user),
            $folder->id,
        );

        try {
            /** @var ?Capability $capability */
            $capability = $this->cache()->remember('acl', $key, $live);

            return $capability;
        } catch (Throwable) {
            // Fail-safe: never let a cache fault deny-by-exception OR serve a
            // stale grant. Resolve live — the same authority used when caching
            // is disabled.
            return $live();
        }
    }

    private function cache(): GenerationalModuleCache
    {
        return $this->cache ??= app(ModuleCacheFactory::class)->generationalFor('resource-library');
    }
}
