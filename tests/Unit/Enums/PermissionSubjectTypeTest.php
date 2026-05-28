<?php

declare(strict_types=1);

use Kurt\Modules\Library\Enums\PermissionSubjectType;

it('has expected cases and values', function () {
    expect(PermissionSubjectType::User->value)->toBe('user');
    expect(PermissionSubjectType::Role->value)->toBe('role');
    expect(PermissionSubjectType::Everyone->value)->toBe('everyone');
});
