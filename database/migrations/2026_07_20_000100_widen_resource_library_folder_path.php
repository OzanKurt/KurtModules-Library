<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `resource_library_folders.path` and bound its index.
 *
 * The original table shipped `path` as VARCHAR(255) with a FULL-length index.
 * The denormalised path stores the whole ancestry ("/slug/slug/..."), so 255
 * chars caps the nestable depth uncomfortably low and a full-length index on a
 * widened column would blow past MySQL's InnoDB key-length limit.
 *
 * This migration widens the column to 1024 and, on MySQL/MariaDB, swaps the
 * full-length index for a bounded 191-char PREFIX index. 191 is the utf8mb4
 * "safe" prefix (191 * 4 = 764 bytes) and is more than enough selectivity for
 * the `path LIKE '/a/b/%'` ancestry/descendant scans, which only ever anchor on
 * the leading segments.
 *
 * MAX-DEPTH EXPECTATION: paths are bounded to 1024 characters. With slugs
 * averaging ~40 chars plus a "/" separator that comfortably supports ~24 levels
 * of nesting; deeper trees should shorten slugs. SQLite does not enforce
 * VARCHAR length and does not support prefix indexes, so it is left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // Drop the full-length index first: it would otherwise exceed the
            // InnoDB key-length limit once the column is widened.
            Schema::table('resource_library_folders', function (Blueprint $table): void {
                $table->dropIndex(['path']);
            });

            Schema::table('resource_library_folders', function (Blueprint $table): void {
                $table->string('path', 1024)->change();
            });

            DB::statement('CREATE INDEX resource_library_folders_path_index ON resource_library_folders (path(191))');

            return;
        }

        if ($driver === 'pgsql') {
            // Postgres indexes the widened column fine; just widen it.
            Schema::table('resource_library_folders', function (Blueprint $table): void {
                $table->string('path', 1024)->change();
            });
        }

        // SQLite: VARCHAR length is not enforced and prefix indexes are N/A;
        // the original full-column index is retained as-is.
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('DROP INDEX resource_library_folders_path_index ON resource_library_folders');

            Schema::table('resource_library_folders', function (Blueprint $table): void {
                $table->string('path', 255)->change();
                $table->index('path');
            });

            return;
        }

        if ($driver === 'pgsql') {
            Schema::table('resource_library_folders', function (Blueprint $table): void {
                $table->string('path', 255)->change();
            });
        }
    }
};
