<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Access;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\Library\Contracts\LibrarySubjectResolver;
use Kurt\Modules\Library\Enums\PermissionSubjectType;
use Kurt\Modules\Library\Values\Subject;

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
