<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Events\FolderPermissionChanged;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->member = StubUser::create(['email' => 'member@example.com']);

    // A fresh, request-scoped access facade: a new L1 memo, but the L2
    // generational cache is built lazily over the app's (persistent, array)
    // cache store — so a second instance in the same test acts as the "next
    // request" reading the same cross-request cache.
    $this->access = fn (): ResourceLibraryAccess => new ResourceLibraryAccess(app(PermissionResolver::class));
});

it('does NOT serve a stale grant after a permission is revoked and the cache is bumped (CRITICAL SAFETY TEST)', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    $permission = FolderPermission::factory()
        ->forUser($this->member->id, Capability::Manage)
        ->create(['folder_id' => $folder->id]);

    // Warm the cross-request cache: the member currently has Manage (>= View).
    expect(($this->access)()->check($this->member, $folder, Capability::View))->toBeTrue();

    // REVOKE out of band (raw delete: no model event, no bump). The cache is
    // real, so a fresh request STILL sees the stale grant — this is exactly the
    // staleness the bump must close.
    DB::table('resource_library_folder_permissions')->where('id', $permission->id)->delete();
    expect(($this->access)()->check($this->member, $folder, Capability::View))->toBeTrue();

    // Fire the revocation signal → BumpAclCache invalidates the whole `acl`
    // scope. The NEXT read must reflect the revocation, not the stale grant.
    FolderPermissionChanged::dispatch($permission);

    expect(($this->access)()->check($this->member, $folder, Capability::View))->toBeFalse();
});

it('reflects a role change without any event because rolesHash is part of the key', function () {
    // A restricted folder granting Download to the "editors" role.
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forRole('editors', Capability::Download)
        ->create(['folder_id' => $folder->id]);

    // Initially the member is in NO role → no access. Warm the cache.
    config()->set('resource-library.roles.resolver', fn ($user) => []);
    expect(($this->access)()->check($this->member, $folder, Capability::Download))->toBeFalse();

    // Grant the member the "editors" role out of band (no domain event exists
    // for role changes). The rolesHash changes, so the key changes, so the old
    // (no-access) entry is never served.
    config()->set('resource-library.roles.resolver', fn ($user) => ['editors']);

    expect(($this->access)()->check($this->member, $folder, Capability::Download))->toBeTrue();
});

it('re-resolves against the new ancestry after a folder move bumps the cache', function () {
    $parentA = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $parentB = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->child($parentA)
        ->create(['owner_id' => $this->owner->id]);

    // A cascading Download grant on parent A reaches the child.
    FolderPermission::factory()
        ->forUser($this->member->id, Capability::Download, cascade: true)
        ->create(['folder_id' => $parentA->id]);

    // Warm: the child inherits Download from A.
    expect(($this->access)()->check($this->member, $child, Capability::Download))->toBeTrue();

    // Move the child under B (no grant). moveTo fires FolderMoved → bump.
    $moved = $child->moveTo($parentB);

    expect(($this->access)()->check($this->member, $moved, Capability::Download))->toBeFalse();
});

it('is fail-safe: a disabled cache reflects an out-of-band change immediately (live)', function () {
    config()->set('resource-library.cache.enabled', false);

    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    $permission = FolderPermission::factory()
        ->forUser($this->member->id, Capability::Manage)
        ->create(['folder_id' => $folder->id]);

    expect(($this->access)()->check($this->member, $folder, Capability::View))->toBeTrue();

    // Revoke out of band: no event, no bump. With caching disabled, resolution
    // is always live, so the change is reflected on the very next read.
    DB::table('resource_library_folder_permissions')->where('id', $permission->id)->delete();

    expect(($this->access)()->check($this->member, $folder, Capability::View))->toBeFalse();
});

it('resolves a repeated (subject, folder) at most once per request (L1 memo)', function () {
    $folder = Folder::factory()
        ->visibility(FolderVisibility::Restricted)
        ->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()
        ->forUser($this->member->id, Capability::Manage)
        ->create(['folder_id' => $folder->id]);

    $access = ($this->access)();

    DB::connection()->flushQueryLog();
    DB::connection()->enableQueryLog();

    $access->check($this->member, $folder, Capability::View);
    $access->check($this->member, $folder, Capability::Manage);

    $folderSelects = collect(DB::connection()->getQueryLog())
        ->filter(fn (array $q): bool => str_contains((string) $q['query'], 'resource_library_folders'))
        ->count();

    expect($folderSelects)->toBe(1);
});
