<?php

declare(strict_types=1);

use Kurt\Modules\Library\Enums\FolderVisibility;

it('has expected cases and values', function () {
    expect(FolderVisibility::Public->value)->toBe('public');
    expect(FolderVisibility::Restricted->value)->toBe('restricted');
    expect(FolderVisibility::Private->value)->toBe('private');
});
