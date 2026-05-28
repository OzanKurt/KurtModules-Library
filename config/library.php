<?php

declare(strict_types=1);

use Kurt\Modules\Library\Access\DefaultSubjectResolver;
use Kurt\Modules\Library\Models\AccessLog;
use Kurt\Modules\Library\Models\Folder;
use Kurt\Modules\Library\Models\FolderPermission;
use Kurt\Modules\Library\Models\Item;
use Kurt\Modules\Library\Models\ItemVersion;
use Kurt\Modules\Library\Models\Tag;

return [
    'media' => [
        'disk' => env('LIBRARY_MEDIA_DISK', 'public'),
        'allowed_mimes' => ['*'],
        'max_size_kb' => 100_000,
        'conversions' => [
            'thumb' => [320, 320],
        ],
    ],
    'versions' => [
        'keep_old' => 10,
    ],
    'subject_resolver' => DefaultSubjectResolver::class,
    'access_log' => [
        'enabled' => true,
        'on_view' => false,
    ],
    'video_link_providers' => ['youtube', 'vimeo', 'dailymotion', 'loom'],
    'models' => [
        'folder' => Folder::class,
        'folder_permission' => FolderPermission::class,
        'item' => Item::class,
        'item_version' => ItemVersion::class,
        'tag' => Tag::class,
        'access_log' => AccessLog::class,
    ],
];
