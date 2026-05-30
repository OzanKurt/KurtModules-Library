<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Access\DefaultSubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

it('returns Everyone subject only when user is null', function () {
    $subjects = (new DefaultSubjectResolver)->subjects(null);

    expect($subjects)->toHaveCount(1);
    expect($subjects[0]->type)->toBe(PermissionSubjectType::Everyone);
    expect($subjects[0]->value)->toBeNull();
});

it('returns Everyone + User subjects when user is supplied', function () {
    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(42);

    $subjects = (new DefaultSubjectResolver)->subjects($user);

    expect($subjects)->toHaveCount(2);
    expect($subjects[0]->type)->toBe(PermissionSubjectType::Everyone);
    expect($subjects[1]->type)->toBe(PermissionSubjectType::User);
    expect($subjects[1]->value)->toBe('42');
});
