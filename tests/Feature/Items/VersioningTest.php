<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
});

it('creates a v1 and updates current_version_id on first newVersion', function () {
    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    expect($item->current_version_id)->toBeNull();

    $v1 = $item->newVersion(['media_path' => 'storage/v1.bin'], $this->owner);

    expect($v1->version)->toBe(1);
    expect($v1->item_id)->toBe($item->id);

    $item->refresh();
    expect($item->current_version_id)->toBe($v1->id);
});

it('increments version number and moves current_version_id on subsequent newVersion', function () {
    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    $v1 = $item->newVersion(['media_path' => 'v1.bin'], $this->owner);
    $v2 = $item->newVersion(['media_path' => 'v2.bin', 'changelog' => 'fix'], $this->owner);

    expect($v2->version)->toBe(2);
    expect($v2->id)->not->toBe($v1->id);

    $item->refresh();
    expect($item->current_version_id)->toBe($v2->id);
    expect($item->versions()->count())->toBe(2);
});

it('assigns contiguous, gap-free version numbers across many newVersion calls', function () {
    // Regression guard for the numbering race: the transactional, locked
    // max('version')+1 read must yield 1..N with no gaps or repeats.
    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        $version = $item->newVersion(['media_path' => "v{$i}.bin"], $this->owner);
        expect($version->version)->toBe($i);
    }

    expect($item->versions()->orderBy('version')->pluck('version')->all())
        ->toBe([1, 2, 3, 4, 5]);
});

it('lets the unique(item_id, version) index reject a duplicate version number as the final backstop', function () {
    // The DB constraint is the last line of defence behind the row lock: two
    // rows can never share (item_id, version), so a raced duplicate is rejected.
    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    $item->newVersion(['media_path' => 'v1.bin'], $this->owner);

    expect(fn () => ItemVersion::query()->create([
        'item_id' => $item->id,
        'version' => 1,
        'created_by' => $this->owner->id,
    ]))->toThrow(QueryException::class);
});
