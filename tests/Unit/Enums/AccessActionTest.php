<?php

declare(strict_types=1);

use Kurt\Modules\Library\Enums\AccessAction;

it('has expected cases and values', function () {
    expect(AccessAction::View->value)->toBe('view');
    expect(AccessAction::Download->value)->toBe('download');
});
