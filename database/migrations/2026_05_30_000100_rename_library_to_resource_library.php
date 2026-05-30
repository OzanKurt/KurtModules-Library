<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, array{0: string, 1: string}> */
    private array $tables = [
        ['library_access_log', 'resource_library_access_log'],
        ['library_folder_permissions', 'resource_library_folder_permissions'],
        ['library_item_tag', 'resource_library_item_tag'],
        ['library_tags', 'resource_library_tags'],
        ['library_item_versions', 'resource_library_item_versions'],
        ['library_items', 'resource_library_items'],
        ['library_folders', 'resource_library_folders'],
    ];

    public function up(): void
    {
        foreach ($this->tables as [$from, $to]) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    public function down(): void
    {
        // Intentionally a no-op. On fresh installs the legacy `library_*`
        // tables never existed and the per-table create migrations already
        // drop the `resource_library_*` tables on rollback. Renaming back
        // would break that cascade. For a v2-to-v3 upgrade rollback,
        // operators should restore from backup rather than rely on this
        // migration's `down()` path.
    }
};
