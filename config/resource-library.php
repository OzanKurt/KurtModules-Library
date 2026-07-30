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
    // REST API surface (Core "API kit"). Safe-by-default: the routes register
    // only when `mode` is `api` (or `ui`); in `headless` nothing is exposed and
    // the module is driven purely through its domain services. Every endpoint
    // still enforces the per-folder ACL (see the Folder/Item policies), so the
    // API never leaks a folder or item the current subject cannot view.
    'http' => [
        'mode' => env('RESOURCE_LIBRARY_HTTP_MODE', 'headless'),
        'prefix' => 'api/resource-library',
        'middleware' => ['api'],
        'auth_middleware' => ['auth'],
        'rate_limit' => '60,1',
    ],
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
    // Cross-request ACL cache (Core "module cache" convention). The per-folder
    // capability resolution (PermissionResolver::highestCapability) walks the
    // ancestor chain and is the family's hottest read; this layer caches it
    // across requests using a generational cache so a permission or move change
    // invalidates the whole ACL keyspace in O(1) (see the FolderPermissionChanged
    // / FolderMoved bump wiring). SECURITY: caching is FAIL-SAFE — a disabled or
    // erroring cache falls through to a LIVE resolution and never grants on
    // error. Keep the TTL short: it is only a defense-in-depth floor for any
    // entry that escapes a bump.
    'cache' => [
        'enabled' => (bool) env('RESOURCE_LIBRARY_CACHE_ENABLED', true),
        'store' => env('RESOURCE_LIBRARY_CACHE_STORE'),
        'prefix' => 'resource-library',
        'ttl' => (int) env('RESOURCE_LIBRARY_CACHE_TTL', 300),
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
