<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Contracts;

interface LibrarySubject
{
    public function getKey(): int|string;

    public function getLibrarySubjectDisplayName(): string;
}
