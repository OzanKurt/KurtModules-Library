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

    $this->folder = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    FolderPermission::factory()->forUser($this->manager->id, Capability::Manage)->create(['folder_id' => $this->folder->id]);
    FolderPermission::factory()->forUser($this->viewer->id, Capability::View)->create(['folder_id' => $this->folder->id]);
});

it('lets a manager grant access (share) and it takes effect', function () {
    $newUser = StubUser::create(['email' => 'new@example.com']);

    $this->actingAs($this->manager)
        ->postJson("api/resource-library/folders/{$this->folder->id}/permissions", [
            'subject_type' => 'user',
            'subject_value' => (string) $newUser->id,
            'capability' => 'download',
            'cascade' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.capability', 'download');

    // The grant is persisted and now resolves for the new subject.
    expect(FolderPermission::query()->where('folder_id', $this->folder->id)->where('subject_value', (string) $newUser->id)->exists())->toBeTrue();

    $this->actingAs($newUser)
        ->getJson("api/resource-library/folders/{$this->folder->id}")
        ->assertOk();
});

it('requires manage to list or grant permissions (viewer is 403)', function () {
    $this->actingAs($this->viewer)
        ->getJson("api/resource-library/folders/{$this->folder->id}/permissions")
        ->assertForbidden();

    $this->actingAs($this->viewer)
        ->postJson("api/resource-library/folders/{$this->folder->id}/permissions", [
            'subject_type' => 'everyone',
            'capability' => 'view',
        ])
        ->assertForbidden();
});

it('lists the grants on a folder for a manager', function () {
    $grants = $this->actingAs($this->manager)
        ->getJson("api/resource-library/folders/{$this->folder->id}/permissions")
        ->assertOk()
        ->json('data');

    expect(count($grants))->toBe(2);
});

it('revokes a grant for a manager', function () {
    $grant = FolderPermission::factory()->forEveryone(Capability::View)->create(['folder_id' => $this->folder->id]);

    $this->actingAs($this->manager)
        ->deleteJson("api/resource-library/folders/{$this->folder->id}/permissions/{$grant->id}")
        ->assertNoContent();

    expect(FolderPermission::query()->find($grant->id))->toBeNull();
});

it('404s revoking a grant that belongs to another folder', function () {
    $other = Folder::factory()->create(['owner_id' => $this->owner->id]);
    $grant = FolderPermission::factory()->forEveryone(Capability::View)->create(['folder_id' => $other->id]);

    $this->actingAs($this->manager)
        ->deleteJson("api/resource-library/folders/{$this->folder->id}/permissions/{$grant->id}")
        ->assertNotFound();
});

it('blocks guests from the grant surface', function () {
    $this->getJson("api/resource-library/folders/{$this->folder->id}/permissions")->assertUnauthorized();
});
