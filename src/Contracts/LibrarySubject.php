<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Contracts;

interface LibrarySubject
{
    public function getKey(): int|string;

    public function getLibrarySubjectDisplayName(): string;
}
