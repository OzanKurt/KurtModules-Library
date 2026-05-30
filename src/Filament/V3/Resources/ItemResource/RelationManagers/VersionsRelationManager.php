<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V3\Resources\ItemResource\RelationManagers;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Versions';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('version'),
                TextEntry::make('external_url')->label('External URL')->placeholder('—'),
                TextEntry::make('media_path')->label('Media path')->placeholder('—'),
                TextEntry::make('mime_type')->label('MIME type')->placeholder('—'),
                TextEntry::make('byte_size')->numeric()->placeholder('—'),
                TextEntry::make('checksum')->placeholder('—'),
                TextEntry::make('changelog')->placeholder('—')->columnSpanFull(),
                TextEntry::make('created_at')->dateTime(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('version', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('mime_type')
                    ->label('MIME type')
                    ->placeholder('—'),
                TextColumn::make('byte_size')
                    ->label('Size')
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('changelog')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
