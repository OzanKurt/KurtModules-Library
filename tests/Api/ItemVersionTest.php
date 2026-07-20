<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->manager = StubUser::create(['email' => 'manager@example.com']);
    $this->viewer = StubUser::create(['email' => 'viewer@example.com']);

    $this->folder = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    FolderPermission::factory()->forUser($this->manager->id, Capability::Manage)->create(['folder_id' => $this->folder->id]);
    FolderPermission::factory()->forUser($this->viewer->id, Capability::View)->create(['folder_id' => $this->folder->id]);

    $this->item = Item::factory()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id]);
});

it('adds a version for a manager and bumps the current version', function () {
    $this->actingAs($this->manager)
        ->postJson("api/library/items/{$this->item->id}/versions", [
            'external_url' => 'https://example.com/v1',
            'changelog' => 'Initial upload',
        ])
        ->assertCreated()
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.changelog', 'Initial upload');

    expect(Item::query()->find($this->item->id)->current_version_id)->not->toBeNull();
});

it('403s adding a version for a viewer', function () {
    $this->actingAs($this->viewer)
        ->postJson("api/library/items/{$this->item->id}/versions", ['changelog' => 'nope'])
        ->assertForbidden();
});

it('lists versions newest-first for a viewer', function () {
    $this->item->newVersion(['changelog' => 'v1'], $this->manager);
    $this->item->newVersion(['changelog' => 'v2'], $this->manager);

    $versions = $this->actingAs($this->viewer)
        ->getJson("api/library/items/{$this->item->id}/versions")
        ->assertOk()
        ->json('data');

    expect(collect($versions)->pluck('version')->all())->toBe([2, 1]);
});

it('403s listing versions for a subject who cannot view the folder', function () {
    $stranger = StubUser::create(['email' => 'stranger@example.com']);

    $this->actingAs($stranger)
        ->getJson("api/library/items/{$this->item->id}/versions")
        ->assertForbidden();
});
