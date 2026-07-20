<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Access\DefaultSubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

it('returns Everyone subject only when user is null', function () {
    $subjects = (new DefaultSubjectResolver)->subjects(null);

    expect($subjects)->toHaveCount(1);
    expect($subjects[0]->type)->toBe(PermissionSubjectType::Everyone);
    expect($subjects[0]->value)->toBeNull();
});
