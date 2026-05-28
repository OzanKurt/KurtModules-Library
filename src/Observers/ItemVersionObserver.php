<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Observers;

use Kurt\Modules\Library\Events\ItemVersionCreated;
use Kurt\Modules\Library\Models\Item;
use Kurt\Modules\Library\Models\ItemVersion;

final class ItemVersionObserver
{
    public function created(ItemVersion $version): void
    {
        ItemVersionCreated::dispatch($version);

        // If the item has no current_version_id yet, set this as current.
        /** @var Item|null $item */
        $item = $version->item;
        if ($item !== null && $item->current_version_id === null) {
            $item->forceFill(['current_version_id' => $version->id])->save();
        }
    }
}
