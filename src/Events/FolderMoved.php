<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class FolderMoved
{
    use Dispatchable;

    public function __construct(public readonly Folder $folder) {}
}
