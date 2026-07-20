<?php

declare(strict_types=1);

use Kurt\Modules\ResourceLibrary\Access\DefaultSubjectResolver;
use Kurt\Modules\ResourceLibrary\Models\AccessLog;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;
use Kurt\Modules\ResourceLibrary\Models\Tag;

return [
    'media' => [
        'disk' => env('RESOURCE_LIBRARY_MEDIA_DISK', 'public'),
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
    'roles' => [
        // Role source for the default resolver. Supply a callable that receives
        // the current authenticatable and returns that subject's role ids (an
        // array or Arrayable/Collection of int|string). When set, the default
        // resolver emits `role` subjects so `role` grants resolve out of the
        // box and the Filament ACL relation managers re-enable the `role`
        // option. Leave null to keep the legacy behaviour (role grants inert,
        // the `role` option hidden in the admin UI).
        //
        // Example:
        //   'resolver' => fn ($user) => $user->roles->pluck('id'),
        //
        // Note: a closure here cannot survive `php artisan config:cache`. If you
        // cache config, bind a custom `subject_resolver` class instead.
        'resolver' => null,
    ],
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
