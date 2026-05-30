<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Enums\AccessAction;

it('has expected cases and values', function () {
    expect(AccessAction::View->value)->toBe('view');
    expect(AccessAction::Download->value)->toBe('download');
});
