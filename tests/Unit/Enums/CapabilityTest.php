<?php

declare(strict_types=1);

use Kurt\Modules\Library\Enums\Capability;

it('has expected cases and values', function () {
    expect(Capability::View->value)->toBe('view');
    expect(Capability::Download->value)->toBe('download');
    expect(Capability::Manage->value)->toBe('manage');
});

it('ranks View below Download below Manage', function () {
    expect(Capability::View->rank())->toBeLessThan(Capability::Download->rank());
    expect(Capability::Download->rank())->toBeLessThan(Capability::Manage->rank());
});

it('has stable rank values', function () {
    expect(Capability::View->rank())->toBe(1);
    expect(Capability::Download->rank())->toBe(2);
    expect(Capability::Manage->rank())->toBe(3);
});
