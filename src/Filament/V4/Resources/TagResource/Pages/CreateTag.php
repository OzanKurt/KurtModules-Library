<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V4\Resources\TagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\TagResource;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;
}
