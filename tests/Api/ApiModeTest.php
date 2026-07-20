<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// This suite runs under ApiTestCase (mode=api), so the route group is live.

it('registers the module API routes in api mode', function () {
    expect(Route::has('resource-library.api.folders.index'))->toBeTrue()
        ->and(Route::has('resource-library.api.items.store'))->toBeTrue()
        ->and(Route::has('resource-library.api.folders.permissions.store'))->toBeTrue();
});

it('applies the configured prefix', function () {
    $route = Route::getRoutes()->getByName('resource-library.api.folders.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('api/library/folders');
});
