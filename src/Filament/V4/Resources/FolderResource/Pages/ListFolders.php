<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource;

class ListFolders extends ListRecords
{
    protected static string $resource = FolderResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
