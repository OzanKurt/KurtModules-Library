<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Observers;

use Kurt\Modules\ResourceLibrary\Events\ItemCreated;
use Kurt\Modules\ResourceLibrary\Events\ItemDeleted;
use Kurt\Modules\ResourceLibrary\Events\ItemPublished;
use Kurt\Modules\ResourceLibrary\Events\ItemUnpublished;
use Kurt\Modules\ResourceLibrary\Events\ItemUpdated;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;

final class ItemObserver
{
    public function created(Item $item): void
    {
        Folder::query()->whereKey($item->folder_id)->increment('item_count');

        ItemCreated::dispatch($item);
    }

    public function updated(Item $item): void
    {
        ItemUpdated::dispatch($item);

        if ($item->wasChanged('published_at')) {
            if ($item->published_at !== null) {
                ItemPublished::dispatch($item);
            } else {
                ItemUnpublished::dispatch($item);
            }
        }

        // If the folder moved, fix item_count on both sides.
        if ($item->wasChanged('folder_id')) {
            $original = (int) $item->getOriginal('folder_id');
            if ($original > 0) {
                Folder::query()->whereKey($original)->decrement('item_count');
            }
            Folder::query()->whereKey($item->folder_id)->increment('item_count');
        }
    }

    public function deleted(Item $item): void
    {
        // Only decrement on hard delete (not soft delete) for symmetry — but
        // here the Item uses SoftDeletes. We always decrement on deletion to
        // mirror what the listing shows (non-trashed only).
        Folder::query()->whereKey($item->folder_id)->decrement('item_count');

        ItemDeleted::dispatch($item);
    }

    public function restored(Item $item): void
    {
        Folder::query()->whereKey($item->folder_id)->increment('item_count');
    }
}
