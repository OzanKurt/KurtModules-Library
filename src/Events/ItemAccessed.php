<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;

final class ItemAccessed
{
    use Dispatchable;

    public function __construct(public readonly AccessLog $log) {}
}
