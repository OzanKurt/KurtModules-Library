<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACL design pins
|--------------------------------------------------------------------------
|
| These tests PIN two deliberate resolution behaviours so they are explicit
| and regression-guarded. They are NOT asserting a "correct" ACL policy —
| they lock in the *current* one so any future change is a conscious, visible
| decision (see the audit tail: both are flagged "intentional or revisit?").
|
| 1. Nearest-ancestor shadowing: the walk stops at the nearest folder that
|    yields a match, so a closer ancestor's grant shadows a farther ancestor's
|    grant even when the farther one is HIGHER.
| 2. Restricted-still-inherits: a Restricted folder still receives cascading
|    grants from ancestors; "Restricted" only caps the visibility *fallback*
|    that applies when nothing matched.
|
*/

use Kurt\Modules\ResourceLibrary\Access\LibraryAccess;
use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->user = StubUser::create(['email' => 'user@example.com']);
});

it('PINS nearest-ancestor shadowing: a closer grant hides a farther higher grant', function () {
    // grandparent grants Manage (cascade); parent grants only View (cascade);
    // the leaf itself has no matching row.
    $grandparent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($grandparent)
        ->create(['owner_id' => $this->owner->id]);
    $leaf = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->user->id, Capability::Manage, cascade: true)
        ->create(['folder_id' => $grandparent->id]);
    FolderPermission::factory()
        ->forUser($this->user->id, Capability::View, cascade: true)
        ->create(['folder_id' => $parent->id]);

    $access = new LibraryAccess(app(PermissionResolver::class));

    // Resolution stops at the parent (nearest match) with View, so the
    // grandparent's higher Manage grant is shadowed and never applied.
    expect($access->check($this->user, $leaf, Capability::View))->toBeTrue();
    expect($access->check($this->user, $leaf, Capability::Download))->toBeFalse();
    expect($access->check($this->user, $leaf, Capability::Manage))->toBeFalse();
});

it('PINS same-folder rows are still compared by rank (highest wins on one folder)', function () {
    // Guards the boundary of the pin above: shadowing is *between* folders;
    // rows on the SAME folder are still merged by highest rank.
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->user->id, Capability::View)
        ->create(['folder_id' => $folder->id]);
    FolderPermission::factory()
        ->forUser($this->user->id, Capability::Manage)
        ->create(['folder_id' => $folder->id]);

    $access = new LibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->user, $folder, Capability::Manage))->toBeTrue();
});

it('PINS Restricted visibility still inherits an ancestor cascade', function () {
    // A Restricted child inherits a cascading ancestor grant; the Restricted
    // visibility does NOT sever inheritance — it only caps the fallback that
    // applies when no permission row matched.
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->user->id, Capability::Download, cascade: true)
        ->create(['folder_id' => $parent->id]);

    $access = new LibraryAccess(app(PermissionResolver::class));

    // Inherited from the Restricted parent even though the child is Restricted.
    expect($access->check($this->user, $child, Capability::Download))->toBeTrue();
});

it('PINS the Restricted fallback denies only when nothing matched in the chain', function () {
    // With no matching rows anywhere, a Restricted folder falls back to "deny".
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    $access = new LibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->user, $child, Capability::View))->toBeFalse();
});
