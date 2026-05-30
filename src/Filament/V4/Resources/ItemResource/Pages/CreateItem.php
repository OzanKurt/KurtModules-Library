<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;
}
