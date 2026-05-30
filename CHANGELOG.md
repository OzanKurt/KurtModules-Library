# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-05-30

### Changed

- **BREAKING:** Composer package renamed from `ozankurt/laravel-modules-library` to `ozankurt/laravel-modules-resource-library`.
- **BREAKING:** PHP namespace renamed from `Kurt\Modules\Library\` to `Kurt\Modules\ResourceLibrary\`.
- **BREAKING:** Config key renamed from `library` to `resource-library`. Update `config('library.foo')` → `config('resource-library.foo')`.
- **BREAKING:** Service provider class renamed from `LibraryServiceProvider` to `ResourceLibraryServiceProvider`.
- **BREAKING:** Artisan signatures renamed from `library:*` to `resource-library:*` (`library:recount` → `resource-library:recount`, etc.).
- **BREAKING:** Database tables renamed from `library_*` to `resource_library_*` via a single auto-migration. Run `php artisan migrate` after upgrading.
- **BREAKING:** Environment variable `LIBRARY_MEDIA_DISK` renamed to `RESOURCE_LIBRARY_MEDIA_DISK`.

### Why

The new `ozankurt/laravel-modules-media-library` package introduces a WordPress-style media bucket. To prevent confusion, the previous Library module — which is a SaaS *resource* library for sharing videos/files/documents with folder ACL — is renamed to ResourceLibrary.

### Compatibility

No functional behavior changes. All model methods, scopes, policy gates, and event class signatures remain identical. The repo URL on GitHub is unchanged.

See [`UPGRADE-3.0.md`](./UPGRADE-3.0.md) for migration steps.

## [2.0.0] - 2026-05-28

Initial release of the `ozankurt/laravel-modules-library` package.

### Added

- Models: `Folder` (translatable, sluggable, soft-deletes), `Item` (translatable, sluggable, soft-deletes, `HasMedia`), `ItemVersion`, `Tag` (translatable), `FolderPermission`, `AccessLog`.
- Enums: `FolderVisibility` (Public, Restricted, Private), `ItemKind` (VideoLink, File, Document, ExternalUrl), `PermissionSubjectType` (User, Role, Everyone), `Capability` (View, Download, Manage), `AccessAction` (View, Download).
- ACL service: `LibraryAccess::check(?Authenticatable, Folder|Item, Capability)` backed by `PermissionResolver` that walks ancestry + visibility fallback. Per-request memoised.
- `LibrarySubjectResolver` contract + `DefaultSubjectResolver` (returns `Everyone` + `User(id)`). Override via `config('library.subject_resolver')`.
- `Folder::moveTo(?Folder $newParent)` — single-query subtree path/depth rewrite.
- `Item::newVersion(array $payload, Model $by): ItemVersion` — increments version, sets `current_version_id`.
- `Item::recordAccess(?Model $user, AccessAction $action)` — writes audit row and bumps download/view counters per config.
- Console commands: `library:recount`, `library:prune-versions`, `library:rebuild-paths`, `library:demo`.
- Events: `FolderCreated/Updated/Deleted/Moved`, `FolderPermissionChanged`, `ItemCreated/Updated/Deleted/Published/Unpublished`, `ItemAccessed`, `ItemVersionCreated`, `TagCreated/Deleted`.
- Observers: `FolderObserver` (auto-builds `path` + `depth` on create), `ItemObserver` (maintains `Folder.item_count`), `ItemVersionObserver`.
- Policies: `FolderPolicy`, `ItemPolicy` — delegate to `LibraryAccess`. `canAdminLibrary` global gate bypasses.
- Migrations: `library_folders`, `library_item_versions`, `library_items`, `library_tags`, `library_item_tag`, `library_folder_permissions`, `library_access_log`.
- Pest 3 test suite covering ACL matrix, `moveTo`, versioning, access log toggles, `library:recount`.
- GitHub Actions CI (Laravel 12, PHP 8.4).

### Deferred

- Filament v3/v4/v5 admin resources will land in v2.1.
