<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Kurt\Modules\ResourceLibrary\Contracts\LibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Enums\PermissionSubjectType;
use Kurt\Modules\ResourceLibrary\Values\Subject;

final class DefaultSubjectResolver implements LibrarySubjectResolver
{
    /**
     * @return array<int, Subject>
     */
    public function subjects(?Authenticatable $user): array
    {
        $subjects = [new Subject(PermissionSubjectType::Everyone, null)];

        if ($user !== null) {
            $subjects[] = new Subject(PermissionSubjectType::User, (string) $user->getAuthIdentifier());

            foreach ($this->roleIds($user) as $roleId) {
                $subjects[] = new Subject(PermissionSubjectType::Role, $roleId);
            }
        }

        return $subjects;
    }

    /**
     * Role identifiers for the user per the configured role source
     * (`resource-library.roles.resolver`). Returns an empty list when no role
     * source is configured, which keeps the default (role-less) behaviour.
     *
     * @return list<string>
     */
    private function roleIds(Authenticatable $user): array
    {
        $resolver = config('resource-library.roles.resolver');

        if (! is_callable($resolver)) {
            return [];
        }

        /** @var mixed $result */
        $result = $resolver($user);

        if ($result instanceof Arrayable) {
            $result = $result->toArray();
        }

        if (! is_iterable($result)) {
            return [];
        }

        $ids = [];

        foreach ($result as $roleId) {
            if (is_scalar($roleId)) {
                $ids[] = (string) $roleId;
            }
        }

        return $ids;
    }
}
