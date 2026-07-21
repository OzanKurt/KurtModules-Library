<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class FolderPolicy
{
    public function __construct(private readonly ResourceLibraryAccess $access) {}

    public function before(?Authenticatable $user, string $ability): ?bool
    {
        if ($user === null) {
            return null;
        }

        /** @var Gate $gate */
        $gate = app(Gate::class);
        if ($gate->forUser($user)->has('canAdminResourceLibrary') && $gate->forUser($user)->allows('canAdminResourceLibrary')) {
            return true;
        }

        return null;
    }

    public function view(?Authenticatable $user, Folder $folder): bool
    {
        return $this->access->check($user, $folder, Capability::View);
    }

    public function download(?Authenticatable $user, Folder $folder): bool
    {
        return $this->access->check($user, $folder, Capability::Download);
    }

    public function manage(?Authenticatable $user, Folder $folder): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->access->check($user, $folder, Capability::Manage);
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Folder $folder): bool
    {
        return $this->manage($user, $folder);
    }

    public function delete(Authenticatable $user, Folder $folder): bool
    {
        return $this->manage($user, $folder);
    }
}
