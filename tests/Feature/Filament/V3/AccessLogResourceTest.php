<?php

declare(strict_types=1);

use Kurt\Modules\Core\Support\FilamentVersion;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\AccessLogResource;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\AccessLogResource\Pages\ListAccessLogs;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament v3 is not installed.');
    }
});

it('targets the AccessLog model and registers only a list page (read-only)', function () {
    expect(AccessLogResource::getModel())->toBe(AccessLog::class)
        ->and(array_keys(AccessLogResource::getPages()))->toBe(['index']);
});

it('is read-only: creation and editing are disabled', function () {
    expect(AccessLogResource::canCreate())->toBeFalse()
        ->and(AccessLogResource::canEdit(new AccessLog))->toBeFalse();
});

it('builds a table with item, user, action badge and timestamp', function () {
    expect(tableColumnNames(AccessLogResource::class, ListAccessLogs::class))
        ->toContain('item.title', 'user.name', 'action', 'occurred_at');
});

it('offers action and date-range filters', function () {
    expect(tableFilterNames(AccessLogResource::class, ListAccessLogs::class))
        ->toContain('action', 'occurred_at');
});

it('exposes only a view action (no edit or delete)', function () {
    $actions = tableActionNames(AccessLogResource::class, ListAccessLogs::class);

    expect($actions)
        ->toContain('view')
        ->not->toContain('edit')
        ->not->toContain('delete');
});
