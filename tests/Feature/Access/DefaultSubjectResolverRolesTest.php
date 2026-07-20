<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Access\DefaultSubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;

it('emits no role subjects when no role source is configured', function () {
    config()->set('resource-library.roles.resolver', null);

    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(42);

    $subjects = (new DefaultSubjectResolver)->subjects($user);

    expect($subjects)->toHaveCount(2);
    expect($subjects[0]->type)->toBe(PermissionSubjectType::Everyone);
    expect($subjects[1]->type)->toBe(PermissionSubjectType::User);
    expect($subjects[1]->value)->toBe('42');
    expect(array_filter($subjects, fn ($s) => $s->type === PermissionSubjectType::Role))->toBeEmpty();
});

it('emits a role subject per id returned by a configured Collection role source', function () {
    config()->set('resource-library.roles.resolver', fn ($user) => collect([7, 'editor']));

    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(42);

    $subjects = (new DefaultSubjectResolver)->subjects($user);

    expect($subjects)->toHaveCount(4);
    expect($subjects[1]->type)->toBe(PermissionSubjectType::User);
    expect($subjects[2]->type)->toBe(PermissionSubjectType::Role);
    expect($subjects[2]->value)->toBe('7');
    expect($subjects[3]->type)->toBe(PermissionSubjectType::Role);
    expect($subjects[3]->value)->toBe('editor');
});

it('accepts a plain array of role ids and casts them to strings', function () {
    config()->set('resource-library.roles.resolver', fn ($user) => [10, 20]);

    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);

    $subjects = (new DefaultSubjectResolver)->subjects($user);

    expect($subjects)->toHaveCount(4);
    expect($subjects[2]->value)->toBe('10');
    expect($subjects[3]->value)->toBe('20');
});

it('ignores a non-callable role source configuration', function () {
    config()->set('resource-library.roles.resolver', 'not-a-callable');

    $user = Mockery::mock(Authenticatable::class);
    $user->shouldReceive('getAuthIdentifier')->andReturn(1);

    $subjects = (new DefaultSubjectResolver)->subjects($user);

    expect($subjects)->toHaveCount(2);
});

it('never emits role subjects for a guest even with a role source configured', function () {
    config()->set('resource-library.roles.resolver', fn ($user) => [1, 2, 3]);

    $subjects = (new DefaultSubjectResolver)->subjects(null);

    expect($subjects)->toHaveCount(1);
    expect($subjects[0]->type)->toBe(PermissionSubjectType::Everyone);
});
