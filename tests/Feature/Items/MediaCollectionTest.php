<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
});

it('registers the file collection on the configured media disk', function () {
    config()->set('resource-library.media.disk', 's3');

    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    $collection = collect($item->getRegisteredMediaCollections())
        ->firstWhere('name', 'file');

    expect($collection)->not->toBeNull();
    expect($collection->diskName)->toBe('s3');
    expect($collection->singleFile)->toBeTrue();
});

it('falls back to the public disk when no media disk is configured', function () {
    config()->set('resource-library.media.disk', null);

    $item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);

    $collection = collect($item->getRegisteredMediaCollections())
        ->firstWhere('name', 'file');

    expect($collection->diskName)->toBe('public');
});
