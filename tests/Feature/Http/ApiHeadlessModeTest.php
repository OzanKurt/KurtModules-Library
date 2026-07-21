<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// This suite runs under the default (headless) TestCase, so the module's API
// surface must be entirely absent — safe-by-default.

it('does not register API routes in headless mode', function () {
    expect(Route::has('resource-library.api.folders.index'))->toBeFalse();
});

it('404s the API endpoints in headless mode', function () {
    $this->getJson('api/resource-library/folders')->assertNotFound();
});
