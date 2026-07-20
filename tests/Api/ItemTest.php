<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Enums\ItemKind;
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
});

it('creates an item in a folder the user manages', function () {
    $this->actingAs($this->manager)
        ->postJson('api/library/items', [
            'folder_id' => $this->folder->id,
            'title' => 'Getting Started',
            'kind' => ItemKind::ExternalUrl->value,
            'external_url' => 'https://example.com/guide',
            'published' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Getting Started')
        ->assertJsonPath('data.resource.url', 'https://example.com/guide');
});

it('403s item creation for a viewer', function () {
    $this->actingAs($this->viewer)
        ->postJson('api/library/items', [
            'folder_id' => $this->folder->id,
            'title' => 'Nope',
            'kind' => ItemKind::Document->value,
        ])
        ->assertForbidden();
});

it('shows an item to a viewer and records the access', function () {
    $item = Item::factory()->externalUrl('https://example.com/x')->published()
        ->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id]);

    $this->actingAs($this->viewer)
        ->getJson("api/library/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $item->id)
        ->assertJsonPath('data.resource.type', 'external_url')
        ->assertJsonPath('data.view_count', 1);
});

it('403s showing an item in a folder the subject cannot view', function () {
    $secret = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $item = Item::factory()->published()->create(['folder_id' => $secret->id, 'owner_id' => $this->owner->id]);

    $this->actingAs($this->viewer)
        ->getJson("api/library/items/{$item->id}")
        ->assertForbidden();
});

it('hides draft items from viewers but shows them to managers', function () {
    Item::factory()->published()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id, 'title' => ['en' => 'Published']]);
    Item::factory()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id, 'title' => ['en' => 'Draft'], 'published_at' => null]);

    $viewerCount = count($this->actingAs($this->viewer)->getJson("api/library/folders/{$this->folder->id}/items")->assertOk()->json('data'));
    $managerCount = count($this->actingAs($this->manager)->getJson("api/library/folders/{$this->folder->id}/items")->assertOk()->json('data'));

    expect($viewerCount)->toBe(1)
        ->and($managerCount)->toBe(2);
});

it('403s the item listing when the subject cannot view the folder', function () {
    $secret = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);

    $this->actingAs($this->viewer)
        ->getJson("api/library/folders/{$secret->id}/items")
        ->assertForbidden();
});

it('updates and publishes an item for a manager', function () {
    $item = Item::factory()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id, 'published_at' => null]);

    $this->actingAs($this->manager)
        ->patchJson("api/library/items/{$item->id}", ['title' => 'Renamed', 'published' => true])
        ->assertOk()
        ->assertJsonPath('data.title', 'Renamed');

    expect(Item::query()->find($item->id)->published_at)->not->toBeNull();
});

it('403s item update/delete for a viewer', function () {
    $item = Item::factory()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id]);

    $this->actingAs($this->viewer)->patchJson("api/library/items/{$item->id}", ['title' => 'x'])->assertForbidden();
    $this->actingAs($this->viewer)->deleteJson("api/library/items/{$item->id}")->assertForbidden();
});

it('deletes an item for a manager', function () {
    $item = Item::factory()->create(['folder_id' => $this->folder->id, 'owner_id' => $this->owner->id]);

    $this->actingAs($this->manager)
        ->deleteJson("api/library/items/{$item->id}")
        ->assertNoContent();

    expect(Item::query()->find($item->id))->toBeNull();
});
