<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Values;

use Kurt\Modules\Library\Enums\PermissionSubjectType;

final readonly class Subject
{
    public function __construct(
        public PermissionSubjectType $type,
        public ?string $value,
    ) {}

    public function matches(string $rowType, ?string $rowValue): bool
    {
        if ($this->type->value !== $rowType) {
            return false;
        }

        if ($this->type === PermissionSubjectType::Everyone) {
            return true;
        }

        return $this->value === $rowValue;
    }
}
