<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class RecountCommand extends Command
{
    protected $signature = 'resource-library:recount';

    protected $description = 'Rebuild denormalised counters (Folder.item_count) from raw rows.';

    public function handle(): int
    {
        $updated = 0;

        Folder::query()->chunkById(200, function ($folders) use (&$updated): void {
            foreach ($folders as $folder) {
                /** @var Folder $folder */
                $count = (int) DB::table('library_items')
                    ->where('folder_id', $folder->id)
                    ->whereNull('deleted_at')
                    ->count();

                if ($folder->item_count !== $count) {
                    $folder->forceFill(['item_count' => $count])->save();
                    $updated++;
                }
            }
        });

        $this->info("Recounted folders. Updated {$updated} row(s).");

        return self::SUCCESS;
    }
}
