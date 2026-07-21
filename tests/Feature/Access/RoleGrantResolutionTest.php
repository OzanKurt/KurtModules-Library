<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->member = StubUser::create(['email' => 'member@example.com']);
    $this->outsider = StubUser::create(['email' => 'outsider@example.com']);

    // A role source that puts $this->member (only) in the "editors" role.
    $memberId = $this->member->id;
    config()->set('resource-library.roles.resolver', fn ($user) => $user->getAuthIdentifier() === $memberId ? ['editors'] : []);
});

it('resolves a role grant for a user in that role when a role source is configured', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forRole('editors', Capability::Download)
        ->create(['folder_id' => $folder->id]);

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->member, $folder, Capability::View))->toBeTrue();
    expect($access->check($this->member, $folder, Capability::Download))->toBeTrue();
    expect($access->check($this->member, $folder, Capability::Manage))->toBeFalse();
});

it('does not resolve a role grant for a user outside that role', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forRole('editors', Capability::Download)
        ->create(['folder_id' => $folder->id]);

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->outsider, $folder, Capability::View))->toBeFalse();
});

it('cascades a role grant from an ancestor folder to a descendant', function () {
    $parent = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parent)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forRole('editors', Capability::Download, cascade: true)
        ->create(['folder_id' => $parent->id]);

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->member, $child, Capability::Download))->toBeTrue();
});

it('leaves a role grant inert when no role source is configured (backward compatible)', function () {
    config()->set('resource-library.roles.resolver', null);

    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forRole('editors', Capability::Download)
        ->create(['folder_id' => $folder->id]);

    $access = new ResourceLibraryAccess(app(PermissionResolver::class));

    expect($access->check($this->member, $folder, Capability::View))->toBeFalse();
});
