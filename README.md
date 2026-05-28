# laravel-modules-library

SaaS **resource library** module for Laravel: nested folders with per-folder permissions, versioned items, mixed kinds (video link, file, document, external URL), translatable content, Spatie medialibrary, access logging.

## Requirements

- PHP 8.4+
- Laravel 12.x or 13.x
- `ozankurt/laravel-modules-core` v2.x

## Installation

```bash
composer require ozankurt/laravel-modules-library
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=library-config
php artisan vendor:publish --tag=library-migrations
php artisan migrate
```

## Concepts

- **Folder** — node in a tree (self-referential `parent_id`). Denormalised `path` column for fast ancestry queries. `visibility` controls fallback behaviour: `public` / `restricted` / `private`.
- **Item** — leaf in a folder. `kind` is one of `video_link`, `file`, `document`, `external_url`. Each kind stores its payload differently.
- **Version** — every mutation creates a new `ItemVersion` row. `current_version_id` points at the active one.
- **Permission** — per-folder ACL. Subject is `user`, `role`, or `everyone`. Grants `view`, `download`, or `manage`. Rows cascade to descendants by default.
- **Access log** — audit row written on `download` (and optionally `view`) of an item.

## What it provides

- Models: `Folder`, `Item`, `ItemVersion`, `Tag`, `FolderPermission`, `AccessLog`.
- Enums: `FolderVisibility`, `ItemKind`, `PermissionSubjectType`, `Capability`, `AccessAction`.
- Access service: `Kurt\Modules\Library\Access\LibraryAccess::check($user, Folder|Item, Capability)`.
- `Folder::moveTo(?Folder $newParent)` — rewrites a whole subtree's `path` + `depth` in one query.
- Policies (`FolderPolicy`, `ItemPolicy`) that delegate to `LibraryAccess`. Global `canAdminLibrary` gate bypasses everything.
- Console commands: `library:recount`, `library:prune-versions`, `library:rebuild-paths`, `library:demo`.
- Domain events: `FolderCreated/Updated/Deleted/Moved`, `ItemCreated/Updated/Published/Unpublished/Deleted`, `ItemVersionCreated`, `ItemAccessed`, `TagCreated/Deleted`, `FolderPermissionChanged`.

## Subject resolver

Permission resolution maps the host application's identity model (user + roles) onto module subjects. The default resolver ships:

```php
[Subject(Everyone, null), Subject(User, (string) $user->getKey())]
```

Apps with role-based access write a small `LibrarySubjectResolver` and bind its FQCN via `config('library.subject_resolver')`.

## Filament

Filament v3/v4/v5 admin resources are planned for v2.1. The package is headless in v2.0.

## License

MIT (c) Ozan Kurt
