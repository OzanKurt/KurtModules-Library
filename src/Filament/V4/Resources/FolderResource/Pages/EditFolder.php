<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource;

class EditFolder extends EditRecord
{
    protected static string $resource = FolderResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
