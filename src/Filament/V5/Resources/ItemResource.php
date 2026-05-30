<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\ItemResource\Pages;
use Kurt\Modules\ResourceLibrary\Filament\V5\Resources\ItemResource\RelationManagers\VersionsRelationManager;
use Kurt\Modules\ResourceLibrary\Models\Item;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocument;

    protected static ?string $recordTitleAttribute = 'title';

    /** @var array<int, string> */
    protected static array $locales = ['en', 'tr'];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Tabs::make('translations')
                            ->tabs(array_map(
                                fn (string $locale): Tab => Tab::make(strtoupper($locale))
                                    ->schema([
                                        TextInput::make("title.{$locale}")
                                            ->label('Title')
                                            ->required($locale === 'en')
                                            ->maxLength(255),
                                        Textarea::make("description.{$locale}")
                                            ->label('Description')
                                            ->rows(3),
                                    ]),
                                static::$locales,
                            ))
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Details')
                    ->schema([
                        Select::make('folder_id')
                            ->relationship('folder', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Folder'),
                        Select::make('kind')
                            ->options(ItemKind::class)
                            ->default(ItemKind::File)
                            ->required()
                            ->live(),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->label('Tags'),
                        DateTimePicker::make('published_at')
                            ->seconds(false),
                        TextInput::make('external_url')
                            ->url()
                            ->maxLength(2048)
                            ->label('External URL')
                            ->visible(fn (Get $get): bool => in_array(
                                $get('kind'),
                                [ItemKind::VideoLink->value, ItemKind::ExternalUrl->value],
                                true,
                            )),
                    ])
                    ->columns(2),

                Section::make('File')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('file')
                            ->collection('file')
                            ->visibility('public'),
                    ])
                    ->visible(fn (Get $get): bool => in_array(
                        $get('kind'),
                        [ItemKind::File->value, ItemKind::Document->value],
                        true,
                    )),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('kind')
                    ->badge()
                    ->color(fn (ItemKind $state): string => match ($state) {
                        ItemKind::VideoLink => 'info',
                        ItemKind::File => 'success',
                        ItemKind::Document => 'primary',
                        ItemKind::ExternalUrl => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('folder.name')
                    ->label('Folder')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Draft')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->options(ItemKind::class),
                TernaryFilter::make('published')
                    ->label('Published')
                    ->nullable()
                    ->attribute('published_at'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            VersionsRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            'create' => Pages\CreateItem::route('/create'),
            'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
