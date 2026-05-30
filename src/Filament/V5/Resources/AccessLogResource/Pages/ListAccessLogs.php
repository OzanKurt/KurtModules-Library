<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources\AccessLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\AccessLogResource;

class ListAccessLogs extends ListRecords
{
    protected static string $resource = AccessLogResource::class;
}
