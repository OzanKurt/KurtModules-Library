<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V5\Resources\FolderResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Kurt\Modules\ResourceLibrary\Access\RoleSubjectSupport;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $title = 'Access control';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('subject_type')
                    ->options(RoleSubjectSupport::subjectTypeOptions())
                    ->default(PermissionSubjectType::Everyone->value)
                    ->helperText(RoleSubjectSupport::enabled()
                        ? null
                        : 'Role grants need a configured role source (resource-library.roles.resolver) or a custom ResourceLibrarySubjectResolver; until one is set they never resolve, so "role" is hidden.')
                    ->required()
                    ->live(),
                TextInput::make('subject_value')
                    ->label('Subject value')
                    ->helperText('User id or role name.')
                    ->visible(fn (Get $get): bool => $get('subject_type') !== PermissionSubjectType::Everyone->value),
                Select::make('capability')
                    ->options(Capability::class)
                    ->default(Capability::View)
                    ->required(),
                Toggle::make('cascade')
                    ->default(true)
                    ->helperText('Cascade to descendant folders.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('capability')
            ->columns([
                TextColumn::make('subject_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject_value')
                    ->placeholder('—'),
                TextColumn::make('capability')
                    ->badge()
                    ->color(fn (Capability $state): string => match ($state) {
                        Capability::View => 'gray',
                        Capability::Download => 'info',
                        Capability::Manage => 'success',
                    })
                    ->sortable(),
                IconColumn::make('cascade')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
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
}
