<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V3\Resources;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\FolderResource\Pages;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\FolderResource\RelationManagers\PermissionsRelationManager;
use Kurt\Modules\ResourceLibrary\Models\Folder;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $recordTitleAttribute = 'name';

    /** @var array<int, string> */
    protected static array $locales = ['en', 'tr'];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Tabs::make('translations')
                            ->tabs(array_map(
                                fn (string $locale): Tab => Tab::make(strtoupper($locale))
                                    ->schema([
                                        TextInput::make("name.{$locale}")
                                            ->label('Name')
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

                Section::make()
                    ->schema([
                        Select::make('parent_id')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Parent folder'),
                        Select::make('visibility')
                            ->options(FolderVisibility::class)
                            ->default(FolderVisibility::Public)
                            ->required(),
                        TextInput::make('position')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('path')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (FolderVisibility $state): string => match ($state) {
                        FolderVisibility::Public => 'success',
                        FolderVisibility::Restricted => 'warning',
                        FolderVisibility::Private => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('item_count')
                    ->label('Items')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('depth')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('path')
            ->filters([
                SelectFilter::make('visibility')
                    ->options(FolderVisibility::class),
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
            PermissionsRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFolders::route('/'),
            'create' => Pages\CreateFolder::route('/create'),
            'edit' => Pages\EditFolder::route('/{record}/edit'),
        ];
    }
}
