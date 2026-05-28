<?php

declare(strict_types=1);

use Kurt\Modules\Library\Access\LibraryAccess;
use Kurt\Modules\Library\Enums\Capability;
use Kurt\Modules\Library\Enums\FolderVisibility;
use Kurt\Modules\Library\Enums\PermissionSubjectType;
use Kurt\Modules\Library\Models\Folder;
use Kurt\Modules\Library\Models\FolderPermission;
use Kurt\Modules\Library\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->other = StubUser::create(['email' => 'other@example.com']);
    $this->access = app(LibraryAccess::class);
});

it('grants View to a guest on a Public folder via visibility fallback', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Public)
        ->create(['owner_id' => $this->owner->id]);

    expect($this->access->check(null, $folder, Capability::View))->toBeTrue();
});

it('denies access to a guest on a Restricted folder', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    expect($this->access->check(null, $folder, Capability::View))->toBeFalse();
    expect($this->access->check(null, $folder, Capability::Download))->toBeFalse();
});

it('grants Manage to the owner of a Private folder', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Private)
        ->create(['owner_id' => $this->owner->id]);

    expect($this->access->check($this->owner, $folder, Capability::Manage))->toBeTrue();
});

it('denies non-owner on a Private folder without an explicit permission row', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Private)
        ->create(['owner_id' => $this->owner->id]);

    // fresh resolver to avoid cached state across users with same folder id
    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $folder, Capability::View))->toBeFalse();
});

it('grants Download to a user with an explicit Download permission row on a Restricted folder', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->other->id, Capability::Download)
        ->create(['folder_id' => $folder->id]);

    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $folder, Capability::View))->toBeTrue();
    expect($access->check($this->other, $folder, Capability::Download))->toBeTrue();
    expect($access->check($this->other, $folder, Capability::Manage))->toBeFalse();
});

it('cascades a permission from a parent folder to a descendant when cascade=true', function () {
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->other->id, Capability::View, cascade: true)
        ->create(['folder_id' => $parent->id]);

    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $child, Capability::View))->toBeTrue();
});

it('does NOT cascade a permission from a parent folder when cascade=false', function () {
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->other->id, Capability::View, cascade: false)
        ->create(['folder_id' => $parent->id]);

    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $child, Capability::View))->toBeFalse();
});

it('uses the highest capability among matching ancestor + self rows', function () {
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    // Lower capability cascaded from ancestor.
    FolderPermission::factory()
        ->forUser($this->other->id, Capability::View, cascade: true)
        ->create(['folder_id' => $parent->id]);

    // Higher capability on the child itself.
    FolderPermission::factory()
        ->forUser($this->other->id, Capability::Download)
        ->create(['folder_id' => $child->id]);

    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $child, Capability::Download))->toBeTrue();
});

it('matches Everyone subjects on a restricted folder', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forEveryone(Capability::View)
        ->create(['folder_id' => $folder->id]);

    expect($this->access->check(null, $folder, Capability::View))->toBeTrue();
    expect($this->access->check(null, $folder, Capability::Download))->toBeFalse();
});

it('checks capability on a folder via an Item proxy', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->other->id, Capability::Download)
        ->create(['folder_id' => $folder->id]);

    $item = \Kurt\Modules\Library\Models\Item::factory()
        ->create(['folder_id' => $folder->id, 'owner_id' => $this->owner->id]);

    $access = new LibraryAccess(app(\Kurt\Modules\Library\Access\PermissionResolver::class));

    expect($access->check($this->other, $item, Capability::Download))->toBeTrue();
});

it('does not match when subject_value differs from the user identifier', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    $other = $this->other;

    FolderPermission::factory()
        ->state([
            'subject_type' => PermissionSubjectType::User,
            'subject_value' => 'definitely-not-' . $other->id,
            'capability' => Capability::Manage,
        ])
        ->create(['folder_id' => $folder->id]);

    expect($this->access->check($other, $folder, Capability::View))->toBeFalse();
});
