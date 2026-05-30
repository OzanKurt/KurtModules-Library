<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\ResourceLibrary\Models\Folder;

final class RebuildPathsCommand extends Command
{
    protected $signature = 'resource-library:rebuild-paths';

    protected $description = 'Recompute path + depth for every folder by walking the tree from each root.';

    public function handle(): int
    {
        $updated = 0;

        Folder::query()->whereNull('parent_id')->get()->each(function (Folder $root) use (&$updated): void {
            $updated += $this->walk($root, parentPath: '', parentDepth: -1);
        });

        $this->info("Rebuilt paths. Updated {$updated} row(s).");

        return self::SUCCESS;
    }

    private function walk(Folder $folder, string $parentPath, int $parentDepth): int
    {
        $expectedPath = $parentPath.'/'.$folder->slug;
        $expectedDepth = $parentDepth + 1;

        $updated = 0;
        if ($folder->path !== $expectedPath || $folder->depth !== $expectedDepth) {
            $folder->forceFill([
                'path' => $expectedPath,
                'depth' => $expectedDepth,
            ])->save();
            $updated++;
        }

        foreach ($folder->children()->get() as $child) {
            $updated += $this->walk($child, $expectedPath, $expectedDepth);
        }

        return $updated;
    }
}
