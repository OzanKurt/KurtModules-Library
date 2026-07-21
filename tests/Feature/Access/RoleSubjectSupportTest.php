<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Access\DefaultSubjectResolver;
use Kurt\Modules\ResourceLibrary\Access\RoleSubjectSupport;
use Kurt\Modules\ResourceLibrary\Contracts\ResourceLibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;
use Kurt\Modules\ResourceLibrary\Values\Subject;

it('reports role subjects as unsupported and hides the role option under the default resolver', function () {
    config()->set('resource-library.subject_resolver', DefaultSubjectResolver::class);

    expect(RoleSubjectSupport::enabled())->toBeFalse();
    expect(RoleSubjectSupport::subjectTypeOptions())
        ->toBe(['user' => 'User', 'everyone' => 'Everyone']);
});

it('reports role subjects as supported and offers the role option under a custom resolver', function () {
    config()->set('resource-library.subject_resolver', CustomRoleAwareResolver::class);

    expect(RoleSubjectSupport::enabled())->toBeTrue();
    expect(RoleSubjectSupport::subjectTypeOptions())
        ->toBe(['user' => 'User', 'role' => 'Role', 'everyone' => 'Everyone']);
});

it('reports role subjects as supported and re-enables the role option when a role source is configured', function () {
    config()->set('resource-library.subject_resolver', DefaultSubjectResolver::class);
    config()->set('resource-library.roles.resolver', fn ($user) => [1]);

    expect(RoleSubjectSupport::enabled())->toBeTrue();
    expect(RoleSubjectSupport::subjectTypeOptions())
        ->toBe(['user' => 'User', 'role' => 'Role', 'everyone' => 'Everyone']);
});

/**
 * Stand-in for a host app's role-aware resolver binding; only its identity
 * (!== DefaultSubjectResolver) matters to RoleSubjectSupport.
 */
final class CustomRoleAwareResolver implements ResourceLibrarySubjectResolver
{
    /** @return array<int, Subject> */
    public function subjects(?Authenticatable $user): array
    {
        return [new Subject(PermissionSubjectType::Role, 'admin')];
    }
}
