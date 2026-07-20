<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Exceptions;

use RuntimeException;

final class CannotMoveFolderException extends RuntimeException
{
    public static function intoItself(): self
    {
        return new self('A folder cannot be moved into itself.');
    }

    public static function intoDescendant(): self
    {
        return new self('A folder cannot be moved into one of its own descendants.');
    }
}
