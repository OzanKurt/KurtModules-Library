<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\Tag;

final class DemoCommand extends Command
{
    protected $signature = 'resource-library:demo';

    protected $description = 'Seed demo folders, tags, and items.';

    public function handle(): int
    {
        $ownerId = (int) (DB::table('users')->value('id') ?? 1);

        $root = Folder::factory()->create(['owner_id' => $ownerId]);
        $child = Folder::factory()->child($root)->create(['owner_id' => $ownerId]);
        Folder::factory()->child($root)->create(['owner_id' => $ownerId]);

        $tags = Tag::factory()->count(3)->create();

        Item::factory()
            ->count(5)
            ->state(fn () => ['folder_id' => $root->id, 'owner_id' => $ownerId])
            ->create()
            ->each(fn (Item $item) => $item->tags()->sync($tags->random(min(2, $tags->count()))->pluck('id')));

        Item::factory()
            ->videoLink()
            ->state(['folder_id' => $child->id, 'owner_id' => $ownerId])
            ->create();

        Item::factory()
            ->externalUrl()
            ->state(['folder_id' => $child->id, 'owner_id' => $ownerId])
            ->create();

        Item::factory()
            ->kind(ItemKind::Document)
            ->state(['folder_id' => $child->id, 'owner_id' => $ownerId])
            ->create();

        $this->info('Demo data seeded.');

        return self::SUCCESS;
    }
}
