<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->manager = StubUser::create(['email' => 'manager@example.com']);
    $this->viewer = StubUser::create(['email' => 'viewer@example.com']);
});

/** Grant a capability to a user on a folder. */
function grant(Folder $folder, StubUser $user, Capability $capability): void
{
    FolderPermission::factory()->forUser($user->id, $capability)->create(['folder_id' => $folder->id]);
}

it('lets a user create a root folder and owns/manages it', function () {
    $this->actingAs($this->manager)
        ->postJson('api/library/folders', ['name' => 'My Library', 'visibility' => 'private'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'My Library')
        ->assertJsonPath('data.owner_id', $this->manager->id);
});

it('requires manage on the parent to create a child folder', function () {
    $parent = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($parent, $this->viewer, Capability::View);

    $this->actingAs($this->viewer)
        ->postJson('api/library/folders', ['name' => 'Child', 'parent_id' => $parent->id])
        ->assertForbidden();
});

it('allows creating a child folder when the user manages the parent', function () {
    $parent = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($parent, $this->manager, Capability::Manage);

    $this->actingAs($this->manager)
        ->postJson('api/library/folders', ['name' => 'Child', 'parent_id' => $parent->id])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $parent->id);
});

it('403s update/delete for a viewer and succeeds for a manager', function () {
    $folder = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($folder, $this->viewer, Capability::View);

    $this->actingAs($this->viewer)
        ->patchJson("api/library/folders/{$folder->id}", ['name' => 'Nope'])
        ->assertForbidden();

    $this->actingAs($this->viewer)
        ->deleteJson("api/library/folders/{$folder->id}")
        ->assertForbidden();

    $manageable = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($manageable, $this->manager, Capability::Manage);

    $this->actingAs($this->manager)
        ->patchJson("api/library/folders/{$manageable->id}", ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');
});

it('deletes a folder the user manages', function () {
    $folder = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($folder, $this->manager, Capability::Manage);

    $this->actingAs($this->manager)
        ->deleteJson("api/library/folders/{$folder->id}")
        ->assertNoContent();

    expect(Folder::query()->find($folder->id))->toBeNull();
});

it('moves a folder only when the user manages BOTH source and target', function () {
    $source = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $target = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($source, $this->manager, Capability::Manage);
    grant($target, $this->manager, Capability::Manage);

    $this->actingAs($this->manager)
        ->postJson("api/library/folders/{$source->id}/move", ['parent_id' => $target->id])
        ->assertOk()
        ->assertJsonPath('data.parent_id', $target->id);

    expect(Folder::query()->find($source->id)->path)->toBe($target->path.'/'.$source->slug);
});

it('403s a move when the user cannot manage the target', function () {
    $source = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $target = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($source, $this->manager, Capability::Manage);
    // No grant on target.

    $this->actingAs($this->manager)
        ->postJson("api/library/folders/{$source->id}/move", ['parent_id' => $target->id])
        ->assertForbidden();
});

it('403s a move when the user cannot manage the source', function () {
    $source = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $target = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    grant($target, $this->manager, Capability::Manage);

    $this->actingAs($this->manager)
        ->postJson("api/library/folders/{$source->id}/move", ['parent_id' => $target->id])
        ->assertForbidden();
});

it('blocks guests from writing', function () {
    $folder = Folder::factory()->visibility(FolderVisibility::Public)->create(['owner_id' => $this->owner->id]);

    $this->postJson('api/library/folders', ['name' => 'X'])->assertUnauthorized();
    $this->deleteJson("api/library/folders/{$folder->id}")->assertUnauthorized();
});
