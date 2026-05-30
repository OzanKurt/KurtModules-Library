<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
});

it('auto-builds path on create when path is empty', function () {
    $root = Folder::create([
        'owner_id' => $this->owner->id,
        'slug' => 'docs',
        'name' => ['en' => 'Docs'],
        'path' => '',
    ]);

    expect($root->path)->toBe('/docs');
    expect($root->depth)->toBe(0);
});

it('auto-builds path on create when nested under a parent', function () {
    $root = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'parent',
        'path' => '/parent',
        'depth' => 0,
    ]);

    $child = Folder::create([
        'parent_id' => $root->id,
        'owner_id' => $this->owner->id,
        'slug' => 'child',
        'name' => ['en' => 'Child'],
        'path' => '',
    ]);

    expect($child->path)->toBe('/parent/child');
    expect($child->depth)->toBe(1);
});
