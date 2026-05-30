<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource\Pages\CreateFolder;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource\Pages\ListFolders;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource\RelationManagers\PermissionsRelationManager;
use Kurt\Modules\ResourceLibrary\Models\Folder;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament v4 is not installed.');
    }
});

it('targets the Folder model and registers its pages', function () {
    expect(FolderResource::getModel())->toBe(Folder::class)
        ->and(array_keys(FolderResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a translatable form with parent and visibility selects', function () {
    $fields = formFieldNames(FolderResource::class, CreateFolder::class);

    expect($fields)
        ->toContain('name.en', 'name.tr')
        ->toContain('description.en', 'description.tr')
        ->toContain('parent_id', 'visibility', 'position');
});

it('builds a table with name, path, visibility badge and item count', function () {
    expect(tableColumnNames(FolderResource::class, ListFolders::class))
        ->toContain('name', 'path', 'visibility', 'item_count');

    expect(tableFilterNames(FolderResource::class, ListFolders::class))
        ->toContain('visibility');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(FolderResource::class, ListFolders::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(FolderResource::class, ListFolders::class))
        ->toContain('delete');
});

it('registers the ACL permissions relation manager', function () {
    expect(FolderResource::getRelations())->toContain(PermissionsRelationManager::class);

    expect(relationManagerColumnNames(PermissionsRelationManager::class))
        ->toContain('subject_type', 'subject_value', 'capability', 'cascade');

    expect(relationManagerFieldNames(PermissionsRelationManager::class))
        ->toContain('subject_type', 'subject_value', 'capability', 'cascade');
});
