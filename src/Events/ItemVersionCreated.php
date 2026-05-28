<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\Library\Models\ItemVersion;

final class ItemVersionCreated
{
    use Dispatchable;

    public function __construct(public readonly ItemVersion $version) {}
}
