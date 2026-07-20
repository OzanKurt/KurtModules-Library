<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\Capability;
use Kurt\Modules\ResourceLibrary\Enums\FolderVisibility;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->member = StubUser::create(['email' => 'member@example.com']);
});

it('scopes the root listing to folders the subject may view (sees A, not sibling B)', function () {
    $a = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $b = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);

    // The member is granted view on A only.
    FolderPermission::factory()->forUser($this->member->id, Capability::View)->create(['folder_id' => $a->id]);

    $response = $this->actingAs($this->member)->getJson('api/library/folders');

    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($a->id)
        ->and($ids)->not->toContain($b->id);
});

it('403s on showing a sibling folder the subject cannot view', function () {
    $b = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);

    $this->actingAs($this->member)
        ->getJson("api/library/folders/{$b->id}")
        ->assertForbidden();
});

it('lets the subject show a folder they were granted view on', function () {
    $a = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    FolderPermission::factory()->forUser($this->member->id, Capability::View)->create(['folder_id' => $a->id]);

    $this->actingAs($this->member)
        ->getJson("api/library/folders/{$a->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $a->id);
});

it('403s the child listing when the subject cannot view the parent', function () {
    $parent = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    Folder::factory()->visibility(FolderVisibility::Restricted)->child($parent)->create(['owner_id' => $this->owner->id]);

    $this->actingAs($this->member)
        ->getJson("api/library/folders?parent={$parent->id}")
        ->assertForbidden();
});

it('lists children once the subject has a cascading view grant on the parent', function () {
    $parent = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);
    $child = Folder::factory()->visibility(FolderVisibility::Restricted)->child($parent)->create(['owner_id' => $this->owner->id]);

    FolderPermission::factory()->forUser($this->member->id, Capability::View, cascade: true)->create(['folder_id' => $parent->id]);

    $ids = collect(
        $this->actingAs($this->member)->getJson("api/library/folders?parent={$parent->id}")->assertOk()->json('data')
    )->pluck('id')->all();

    expect($ids)->toContain($child->id);
});

it('lets a guest see a public folder but not a restricted one', function () {
    $public = Folder::factory()->visibility(FolderVisibility::Public)->create(['owner_id' => $this->owner->id]);
    $restricted = Folder::factory()->visibility(FolderVisibility::Restricted)->create(['owner_id' => $this->owner->id]);

    $ids = collect($this->getJson('api/library/folders')->assertOk()->json('data'))->pluck('id')->all();

    expect($ids)->toContain($public->id)
        ->and($ids)->not->toContain($restricted->id);

    $this->getJson("api/library/folders/{$restricted->id}")->assertForbidden();
});
