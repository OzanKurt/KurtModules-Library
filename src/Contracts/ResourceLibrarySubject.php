<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Contracts;

interface ResourceLibrarySubject
{
    public function getKey(): int|string;

    public function getResourceLibrarySubjectDisplayName(): string;
}
