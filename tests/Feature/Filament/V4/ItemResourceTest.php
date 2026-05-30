<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource\Pages\CreateItem;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource\Pages\ListItems;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource\RelationManagers\VersionsRelationManager;
use Kurt\Modules\ResourceLibrary\Models\Item;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament v4 is not installed.');
    }
});

it('targets the Item model and registers its pages', function () {
    expect(ItemResource::getModel())->toBe(Item::class)
        ->and(array_keys(ItemResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a translatable form with kind, folder, conditional url and media fields', function () {
    $fields = formFieldNames(ItemResource::class, CreateItem::class);

    expect($fields)
        ->toContain('title.en', 'title.tr')
        ->toContain('description.en', 'description.tr')
        // Enum + relationship selects.
        ->toContain('kind', 'folder_id', 'tags', 'published_at')
        // Conditional external URL (video_link / external_url kinds).
        ->toContain('external_url')
        // Spatie media library upload (file / document kinds).
        ->toContain('file');
});

it('builds a table with kind filter and published filter', function () {
    expect(tableColumnNames(ItemResource::class, ListItems::class))
        ->toContain('title', 'kind', 'folder.name', 'published_at');

    expect(tableFilterNames(ItemResource::class, ListItems::class))
        ->toContain('kind', 'published');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(ItemResource::class, ListItems::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(ItemResource::class, ListItems::class))
        ->toContain('delete');
});

it('registers the versions relation manager', function () {
    expect(ItemResource::getRelations())->toContain(VersionsRelationManager::class);

    expect(relationManagerColumnNames(VersionsRelationManager::class))
        ->toContain('version', 'changelog', 'created_at');
});
