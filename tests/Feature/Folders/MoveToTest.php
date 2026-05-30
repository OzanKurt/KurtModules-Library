<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
});

it('rewrites a 5-descendant subtree path + depth when moving under a new parent', function () {
    /**
     * Build:
     *   /alpha
     *     /alpha/one
     *       /alpha/one/leaf1
     *     /alpha/two
     *   /beta
     */
    $alpha = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha',
        'depth' => 0,
    ]);
    $one = Folder::factory()->create([
        'parent_id' => $alpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'one',
        'path' => '/alpha/one',
        'depth' => 1,
    ]);
    $leaf1 = Folder::factory()->create([
        'parent_id' => $one->id,
        'owner_id' => $this->owner->id,
        'slug' => 'leaf1',
        'path' => '/alpha/one/leaf1',
        'depth' => 2,
    ]);
    $two = Folder::factory()->create([
        'parent_id' => $alpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'two',
        'path' => '/alpha/two',
        'depth' => 1,
    ]);
    $beta = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'beta',
        'path' => '/beta',
        'depth' => 0,
    ]);

    // Move alpha under beta.
    $alpha->moveTo($beta);

    expect(Folder::find($alpha->id)->path)->toBe('/beta/alpha');
    expect(Folder::find($alpha->id)->depth)->toBe(1);

    expect(Folder::find($one->id)->path)->toBe('/beta/alpha/one');
    expect(Folder::find($one->id)->depth)->toBe(2);

    expect(Folder::find($leaf1->id)->path)->toBe('/beta/alpha/one/leaf1');
    expect(Folder::find($leaf1->id)->depth)->toBe(3);

    expect(Folder::find($two->id)->path)->toBe('/beta/alpha/two');
    expect(Folder::find($two->id)->depth)->toBe(2);
});

it('promotes a folder to root when moveTo(null) is called', function () {
    $alpha = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha',
        'depth' => 0,
    ]);
    $child = Folder::factory()->create([
        'parent_id' => $alpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'child',
        'path' => '/alpha/child',
        'depth' => 1,
    ]);

    $child->moveTo(null);

    $fresh = Folder::find($child->id);
    expect($fresh->parent_id)->toBeNull();
    expect($fresh->path)->toBe('/child');
    expect($fresh->depth)->toBe(0);
});
