<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\AccessAction;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Tests\Stubs\StubUser;

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

it('skips View log rows by default but logs when on_view config is enabled', function () {
    // on_view gates only the AccessLog row; the view_count counter is
    // decoupled and increments on every view regardless (see regression test).
    config()->set('resource-library.access_log.on_view', false);
    $log = $this->item->recordAccess($this->owner, AccessAction::View);
    expect($log)->toBeNull();
    expect(AccessLog::count())->toBe(0);

    config()->set('resource-library.access_log.on_view', true);
    $log2 = $this->item->recordAccess($this->owner, AccessAction::View);
    expect($log2)->not->toBeNull();
    expect(AccessLog::count())->toBe(1);
});

it('increments view_count even when on_view logging is disabled', function () {
    config()->set('resource-library.access_log.on_view', false);

    $log = $this->item->recordAccess($this->owner, AccessAction::View);

    // No log row is written...
    expect($log)->toBeNull();
    expect(AccessLog::count())->toBe(0);

    // ...but the engagement counter still advances (counting is decoupled
    // from logging).
    expect($this->item->fresh()->view_count)->toBe(1);

    $this->item->recordAccess($this->owner, AccessAction::View);
    expect($this->item->fresh()->view_count)->toBe(2);
    expect(AccessLog::count())->toBe(0);
});

it('skips all logging when access_log.enabled is false', function () {
    config()->set('resource-library.access_log.enabled', false);
    expect($this->item->recordAccess($this->owner, AccessAction::Download))->toBeNull();
    expect(AccessLog::count())->toBe(0);
});

it('logs accesses with a nullable user (anonymous)', function () {
    $log = $this->item->recordAccess(null, AccessAction::Download);

    expect($log)->not->toBeNull();
    expect($log->user_id)->toBeNull();
});
