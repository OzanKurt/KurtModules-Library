<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Kurt\Modules\ResourceLibrary\Values\Subject;

interface ResourceLibrarySubjectResolver
{
    /** @return array<int, Subject> */
    public function subjects(?Authenticatable $user): array;
}
