<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Policies;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Library\Access\LibraryAccess;
use Kurt\Modules\Library\Enums\Capability;
use Kurt\Modules\Library\Models\Folder;

final class FolderPolicy
{
    public function __construct(private readonly LibraryAccess $access) {}

    public function before(?Authenticatable $user, string $ability): ?bool
    {
        if ($user === null) {
            return null;
        }

        /** @var Gate $gate */
        $gate = app(Gate::class);
        if ($gate->forUser($user)->has('canAdminLibrary') && $gate->forUser($user)->allows('canAdminLibrary')) {
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
