<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;

final class LibraryAccess
{
    /** @var array<string, ?Capability> */
    private array $cache = [];

    public function __construct(private readonly PermissionResolver $resolver) {}

    public function check(?Authenticatable $user, Folder|Item $target, Capability $needed): bool
    {
        $folder = $target instanceof Item ? $target->folder : $target;
        $key = sprintf('%s:%d', $user?->getAuthIdentifier() ?? 'guest', $folder->id);

        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = $this->resolver->highestCapability($user, $folder);
        }

        $best = $this->cache[$key];

        return $best !== null && $best->rank() >= $needed->rank();
    }

    public function flush(): void
    {
        $this->cache = [];
    }
}
