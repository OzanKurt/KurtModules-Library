<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\ResourceLibrary\Filament\ResourceLibraryPlugin;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\AccessLogResource;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\FolderResource;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\ItemResource;
use Kurt\Modules\ResourceLibrary\Filament\V4\Resources\TagResource;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament v4 is not installed.');
    }
});

it('dispatches the facade to the v4 plugin', function () {
    expect(ResourceLibraryPlugin::make())->toBeInstanceOf(Kurt\Modules\ResourceLibrary\Filament\V4\ResourceLibraryPlugin::class)
        ->and(ResourceLibraryPlugin::make()->getId())->toBe('kurtmodules-resource-library');
});

it('registers all four resource library resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(FolderResource::class)
        ->toContain(ItemResource::class)
        ->toContain(TagResource::class)
        ->toContain(AccessLogResource::class);
});

it('registers routes for every resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/folders', 'admin/folders/create', 'admin/folders/{record}/edit')
        ->toContain('admin/items', 'admin/items/create', 'admin/items/{record}/edit')
        ->toContain('admin/tags')
        ->toContain('admin/access-logs');
});
