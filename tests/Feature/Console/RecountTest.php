<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Library\Models\Folder;
use Kurt\Modules\Library\Models\Item;
use Kurt\Modules\Library\Tests\Stubs\StubUser;

beforeEach(function () {
    $this->owner = StubUser::create(['email' => 'owner@example.com']);
});

it('rebuilds Folder.item_count after corruption', function () {
    $folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
    Item::factory()->count(3)->create([
        'folder_id' => $folder->id,
        'owner_id' => $this->owner->id,
    ]);

    // Corrupt the counter directly.
    DB::table('library_folders')->where('id', $folder->id)->update(['item_count' => 999]);
    expect(Folder::find($folder->id)->item_count)->toBe(999);

    $this->artisan('library:recount')->assertSuccessful();

    expect(Folder::find($folder->id)->item_count)->toBe(3);
});

it('does not change correct counters', function () {
    $folder = Folder::factory()->create(['owner_id' => $this->owner->id]);
    Item::factory()->count(2)->create([
        'folder_id' => $folder->id,
        'owner_id' => $this->owner->id,
    ]);

    // ItemObserver should keep this in sync already.
    expect(Folder::find($folder->id)->item_count)->toBe(2);

    $this->artisan('library:recount')->assertSuccessful();

    expect(Folder::find($folder->id)->item_count)->toBe(2);
});
