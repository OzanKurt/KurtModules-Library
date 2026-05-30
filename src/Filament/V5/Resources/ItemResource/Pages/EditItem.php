<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources\ItemResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\ItemResource;

class EditItem extends EditRecord
{
    protected static string $resource = ItemResource::class;

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
