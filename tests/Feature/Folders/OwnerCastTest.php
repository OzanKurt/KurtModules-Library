<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
});

it('casts folder owner_id to an integer', function () {
    $folder = Folder::factory()->create(['owner_id' => $this->owner->id]);

    $fresh = Folder::find($folder->id);

    expect($fresh->owner_id)->toBeInt();
    expect($fresh->owner_id)->toBe((int) $this->owner->id);
});

it('casts item owner_id to an integer', function () {
    $folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
    $item = Item::factory()->create([
        'folder_id' => $folder->id,
        'owner_id' => $this->owner->id,
    ]);

    $fresh = Item::find($item->id);

    expect($fresh->owner_id)->toBeInt();
    expect($fresh->owner_id)->toBe((int) $this->owner->id);
});
