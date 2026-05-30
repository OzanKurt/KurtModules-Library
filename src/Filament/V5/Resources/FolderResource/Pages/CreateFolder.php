<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources\FolderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\FolderResource;

class CreateFolder extends CreateRecord
{
    protected static string $resource = FolderResource::class;
}
