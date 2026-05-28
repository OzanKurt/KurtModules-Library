<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Enums;

enum ItemKind: string
{
    case VideoLink = 'video_link';
    case File = 'file';
    case Document = 'document';
    case ExternalUrl = 'external_url';
}
