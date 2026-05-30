<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\ResourceLibrary\Enums\AccessAction;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\AccessLogResource\Pages;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;

class AccessLogResource extends Resource
{
    protected static ?string $model = AccessLog::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('item.title')->label('Item'),
                TextEntry::make('user.name')->label('User')->placeholder('Anonymous'),
                TextEntry::make('action')->badge(),
                TextEntry::make('ip')->label('IP address'),
                TextEntry::make('user_agent')->label('User agent'),
                TextEntry::make('occurred_at')->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('item.title')
                    ->label('Item')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Anonymous')
                    ->toggleable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (AccessAction $state): string => match ($state) {
                        AccessAction::View => 'gray',
                        AccessAction::Download => 'success',
                    })
                    ->sortable(),
                TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('occurred_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options(AccessAction::class),
                Filter::make('occurred_at')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('occurred_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('occurred_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccessLogs::route('/'),
        ];
    }
}
