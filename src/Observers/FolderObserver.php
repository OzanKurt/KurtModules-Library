<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Observers;

use Kurt\Modules\ResourceLibrary\Events\FolderCreated;
use Kurt\Modules\ResourceLibrary\Events\FolderDeleted;
use Kurt\Modules\ResourceLibrary\Events\FolderMoved;
use Kurt\Modules\ResourceLibrary\Events\FolderUpdated;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class FolderObserver
{
    public function creating(Folder $folder): void
    {
        $current = $folder->getAttribute('path');
        if ($current === null || $current === '') {
            $parent = $folder->parent_id !== null ? Folder::find($folder->parent_id) : null;
            $parentPath = $parent !== null ? $parent->path : '';
            $folder->path = $parentPath.'/'.$folder->slug;
            $folder->depth = $parent !== null ? $parent->depth + 1 : 0;
        }
    }

    public function created(Folder $folder): void
    {
        FolderCreated::dispatch($folder);
    }

    public function updated(Folder $folder): void
    {
        FolderUpdated::dispatch($folder);

        if ($folder->wasChanged('parent_id') || $folder->wasChanged('path')) {
            FolderMoved::dispatch($folder);
        }
    }

    public function deleted(Folder $folder): void
    {
        FolderDeleted::dispatch($folder);
    }
}
