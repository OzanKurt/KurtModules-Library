<?php

declare(strict_types=1);

use Kurt\Modules\Library\Enums\AccessAction;
use Kurt\Modules\Library\Models\AccessLog;
use Kurt\Modules\Library\Models\Folder;
use Kurt\Modules\Library\Models\Item;
use Kurt\Modules\Library\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
    $this->folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
    $this->item = Item::factory()->create([
        'folder_id' => $this->folder->id,
        'owner_id' => $this->owner->id,
    ]);
});

it('logs Download accesses and increments download_count', function () {
    $log = $this->item->recordAccess($this->owner, AccessAction::Download);

    expect($log)->not->toBeNull();
    expect(AccessLog::count())->toBe(1);
    expect($log->action)->toBe(AccessAction::Download);
    expect($this->item->fresh()->download_count)->toBe(1);
});

it('skips View accesses by default but logs when on_view config is enabled', function () {
    config()->set('library.access_log.on_view', false);
    $log = $this->item->recordAccess($this->owner, AccessAction::View);
    expect($log)->toBeNull();
    expect(AccessLog::count())->toBe(0);
    expect($this->item->fresh()->view_count)->toBe(0);

    config()->set('library.access_log.on_view', true);
    $log2 = $this->item->recordAccess($this->owner, AccessAction::View);
    expect($log2)->not->toBeNull();
    expect(AccessLog::count())->toBe(1);
    expect($this->item->fresh()->view_count)->toBe(1);
});

it('skips all logging when access_log.enabled is false', function () {
    config()->set('library.access_log.enabled', false);
    expect($this->item->recordAccess($this->owner, AccessAction::Download))->toBeNull();
    expect(AccessLog::count())->toBe(0);
});

it('logs accesses with a nullable user (anonymous)', function () {
    $log = $this->item->recordAccess(null, AccessAction::Download);

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBeNull();
});
