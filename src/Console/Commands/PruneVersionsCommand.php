<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;

final class PruneVersionsCommand extends Command
{
    protected $signature = 'resource-library:prune-versions';

    protected $description = 'For each item, retain the newest N versions (config resource-library.versions.keep_old) plus the current_version_id; delete the rest.';

    public function handle(): int
    {
        $keep = config('resource-library.versions.keep_old');

        if ($keep === null) {
            $this->info('resource-library.versions.keep_old is null — keeping all versions.');

            return self::SUCCESS;
        }

        $keepN = (int) $keep;
        $deleted = 0;

        Item::query()->chunkById(100, function ($items) use ($keepN, &$deleted): void {
            foreach ($items as $item) {
                /** @var Item $item */
                /** @var array<int, int> $ids ordered newest-first */
                $ids = $item->versions()
                    ->orderByDesc('version')
                    ->pluck('id')
                    ->all();

                if (count($ids) <= $keepN) {
                    continue;
                }

                $keepIds = array_slice($ids, 0, $keepN);
                if ($item->current_version_id !== null && ! in_array($item->current_version_id, $keepIds, true)) {
                    $keepIds[] = $item->current_version_id;
                }

                $toDelete = array_diff($ids, $keepIds);
                if ($toDelete === []) {
                    continue;
                }

                $deleted += ItemVersion::query()->whereIn('id', $toDelete)->delete();
            }
        });

        $this->info("Pruned {$deleted} version row(s).");

        return self::SUCCESS;
    }
}
