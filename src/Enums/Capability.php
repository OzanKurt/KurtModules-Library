<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Enums;

enum Capability: string
{
    case View = 'view';
    case Download = 'download';
    case Manage = 'manage';

    public function rank(): int
    {
        return match ($this) {
            self::View => 1,
            self::Download => 2,
            self::Manage => 3,
        };
    }
}
