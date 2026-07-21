<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ACL design pins
|--------------------------------------------------------------------------
|
| These tests PIN two deliberate resolution behaviours so they are explicit
| and regression-guarded.
|
| 1. Additive / most-permissive-wins: resolution accumulates the MAXIMUM
|    capability across the WHOLE ancestor chain (self + every cascade-eligible
|    ancestor + the visibility fallback). A closer, lower grant no longer
|    shadows a farther, higher one — the highest grant anywhere in the chain
|    wins. (This replaced the previous "nearest-ancestor wins" model, which
|    had the footgun that a closer `Everyone: View` silently downgraded a user
|    granted `Manage` on a farther ancestor.)
| 2. Restricted-still-inherits: a Restricted folder still receives cascading
|    grants from ancestors; "Restricted" only caps the visibility *fallback*
|    that applies when nothing else matched.
|
*/

use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->user = StubUser::create(['email' => 'user@example.com']);
});

it('PINS additive resolution: a farther higher grant is NOT shadowed by a closer lower grant', function () {
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

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    // Resolution takes the MAX across the whole chain, so the grandparent's
    // higher Manage grant wins over the parent's closer View grant.
    expect($access->check($this->user, $leaf, Capability::View))->toBeTrue();
    expect($access->check($this->user, $leaf, Capability::Download))->toBeTrue();
    expect($access->check($this->user, $leaf, Capability::Manage))->toBeTrue();
});

it('PINS the exact downgrade case: grandparent user:Manage + parent Everyone:View resolves Manage', function () {
    // The precise footgun the additive model removes: a broad, closer
    // `Everyone: View` must NOT downgrade a user granted `Manage` further up.
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
        ->forEveryone(Capability::View, cascade: true)
        ->create(['folder_id' => $parent->id]);

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    // The user keeps Manage; the closer Everyone:View does not cap them.
    expect($access->check($this->user, $leaf, Capability::Manage))->toBeTrue();
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

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

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

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

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

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->user, $child, Capability::View))->toBeFalse();
});
