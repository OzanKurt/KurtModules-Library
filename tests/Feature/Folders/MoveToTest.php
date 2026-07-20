<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Exceptions\CannotMoveFolderException;
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

it('throws and leaves the tree unchanged when moving a folder into itself', function () {
    $alpha = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha',
        'depth' => 0,
    ]);

    expect(fn () => $alpha->moveTo($alpha))
        ->toThrow(CannotMoveFolderException::class);

    $fresh = Folder::find($alpha->id);
    expect($fresh->parent_id)->toBeNull();
    expect($fresh->path)->toBe('/alpha');
    expect($fresh->depth)->toBe(0);
});

it('throws and leaves the tree unchanged when moving a folder into its own child', function () {
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
    $grandchild = Folder::factory()->create([
        'parent_id' => $child->id,
        'owner_id' => $this->owner->id,
        'slug' => 'grandchild',
        'path' => '/alpha/child/grandchild',
        'depth' => 2,
    ]);

    expect(fn () => $alpha->moveTo($child))
        ->toThrow(CannotMoveFolderException::class);

    // Also reject moving into a deeper descendant.
    expect(fn () => $alpha->moveTo($grandchild))
        ->toThrow(CannotMoveFolderException::class);

    // Tree is untouched.
    expect(Folder::find($alpha->id)->path)->toBe('/alpha');
    expect(Folder::find($alpha->id)->parent_id)->toBeNull();
    expect(Folder::find($child->id)->path)->toBe('/alpha/child');
    expect(Folder::find($child->id)->depth)->toBe(1);
    expect(Folder::find($grandchild->id)->path)->toBe('/alpha/child/grandchild');
    expect(Folder::find($grandchild->id)->depth)->toBe(2);
});

it('throws a domain exception (not a raw QueryException) when the destination has a slug collision', function () {
    // Both /alpha and /beta already contain a "report" child. Moving
    // /alpha/report under /beta collides with /beta/report on the
    // unique(parent_id, slug) index; the guard must surface a domain exception.
    $alpha = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha',
        'depth' => 0,
    ]);
    $beta = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'beta',
        'path' => '/beta',
        'depth' => 0,
    ]);
    $moving = Folder::factory()->create([
        'parent_id' => $alpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'report',
        'path' => '/alpha/report',
        'depth' => 1,
    ]);
    Folder::factory()->create([
        'parent_id' => $beta->id,
        'owner_id' => $this->owner->id,
        'slug' => 'report',
        'path' => '/beta/report',
        'depth' => 1,
    ]);

    expect(fn () => $moving->moveTo($beta))
        ->toThrow(CannotMoveFolderException::class);

    // The moving folder is left untouched (guard runs before any writes).
    $fresh = Folder::find($moving->id);
    expect($fresh->parent_id)->toBe($alpha->id);
    expect($fresh->path)->toBe('/alpha/report');
    expect($fresh->depth)->toBe(1);
});

it('rewrites only the anchored path prefix when a descendant slug repeats an ancestor slug', function () {
    /**
     * Build a subtree where the descendant path repeats the "alpha" slug:
     *   /alpha
     *     /alpha/alpha
     *       /alpha/alpha/x
     *   /beta
     *
     * An unanchored REPLACE(path, '/alpha', '/beta/alpha') would corrupt
     * /alpha/alpha/x into /beta/alpha/beta/alpha/x by substituting every
     * occurrence. Only the leading prefix must change.
     */
    $alpha = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha',
        'depth' => 0,
    ]);
    $innerAlpha = Folder::factory()->create([
        'parent_id' => $alpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'alpha',
        'path' => '/alpha/alpha',
        'depth' => 1,
    ]);
    $x = Folder::factory()->create([
        'parent_id' => $innerAlpha->id,
        'owner_id' => $this->owner->id,
        'slug' => 'x',
        'path' => '/alpha/alpha/x',
        'depth' => 2,
    ]);
    $beta = Folder::factory()->create([
        'owner_id' => $this->owner->id,
        'slug' => 'beta',
        'path' => '/beta',
        'depth' => 0,
    ]);

    $alpha->moveTo($beta);

    expect(Folder::find($alpha->id)->path)->toBe('/beta/alpha');
    expect(Folder::find($alpha->id)->depth)->toBe(1);

    expect(Folder::find($innerAlpha->id)->path)->toBe('/beta/alpha/alpha');
    expect(Folder::find($innerAlpha->id)->depth)->toBe(2);

    expect(Folder::find($x->id)->path)->toBe('/beta/alpha/alpha/x');
    expect(Folder::find($x->id)->depth)->toBe(3);
});
