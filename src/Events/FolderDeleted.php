<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Library\Models\Folder;

final class FolderDeleted
{
    use Dispatchable;

    public function __construct(public readonly Folder $folder) {}
}
