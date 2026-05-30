<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Access;

use Illuminate\Contracts\Auth\Authenticatable;
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
        }

        return $subjects;
    }
}
