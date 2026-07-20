<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

/**
 * Build a linear folder chain `$levels` deep and return the deepest (leaf)
 * folder. Every folder is Restricted, so resolution depends purely on the
 * permission rows we attach — not on a visibility shortcut.
 */
function makeFolderChain(int $levels, int $ownerId): Folder
{
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $ownerId]);

    for ($i = 1; $i < $levels; $i++) {
        $folder = Folder::factory()
            ->visibility(FolderVisibility::Restricted)
            ->child($folder)
            ->create(['owner_id' => $ownerId]);
    }

    return $folder;
}

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->user = StubUser::create(['email' => 'viewer@example.com']);
});

it('bounds resolver query count for a deep folder and does not grow with depth', function () {
    $resolver = app(PermissionResolver::class);

    $deepLeaf = makeFolderChain(5, $this->owner->id);
    $shallowLeaf = makeFolderChain(2, $this->owner->id);

    // Put a cascading grant on the ROOT of each chain so resolution must walk
    // the entire ancestry (nearest-match is the farthest ancestor), i.e. the
    // worst case for the old per-level N+1. The root's path is the first
    // anchored segment of the leaf path ("/a/b/c/d/e" -> "/a").
    $rootPath = fn (string $leafPath): string => '/'.explode('/', $leafPath)[1];

    $deepRoot = Folder::query()->where('path', $rootPath($deepLeaf->path))->firstOrFail();
    $shallowRoot = Folder::query()->where('path', $rootPath($shallowLeaf->path))->firstOrFail();

    FolderPermission::factory()
        ->forUser($this->user->id, Capability::View, cascade: true)
        ->create(['folder_id' => $deepRoot->id]);
    FolderPermission::factory()
        ->forUser($this->user->id, Capability::View, cascade: true)
        ->create(['folder_id' => $shallowRoot->id]);

    DB::enableQueryLog();

    DB::flushQueryLog();
    expect($resolver->highestCapability($this->user, $deepLeaf))->toBe(Capability::View);
    $deepCount = count(DB::getQueryLog());

    DB::flushQueryLog();
    expect($resolver->highestCapability($this->user, $shallowLeaf))->toBe(Capability::View);
    $shallowCount = count(DB::getQueryLog());

    DB::disableQueryLog();

    // The key property: query count does not grow with folder depth.
    expect($deepCount)->toBe($shallowCount);

    // And it stays small (chain load + eager permissions load), independent of
    // the 5 levels involved — nothing close to the old O(depth) behaviour.
    expect($deepCount)->toBeLessThanOrEqual(3);
});
