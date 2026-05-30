<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates resource_library_* tables and not library_* tables on fresh install', function () {
    foreach ([
        'resource_library_folders', 'resource_library_items', 'resource_library_item_versions',
        'resource_library_tags', 'resource_library_item_tag', 'resource_library_folder_permissions',
        'resource_library_access_log',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("missing {$table}");
    }
    foreach ([
        'library_folders', 'library_items', 'library_item_versions',
        'library_tags', 'library_item_tag', 'library_folder_permissions',
        'library_access_log',
    ] as $oldTable) {
        expect(Schema::hasTable($oldTable))->toBeFalse("legacy table {$oldTable} should not exist on fresh install");
    }
});
