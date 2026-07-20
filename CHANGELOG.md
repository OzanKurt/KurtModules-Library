# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Config-driven role subjects for the default resolver. Set `resource-library.roles.resolver` to a callable (e.g. `fn ($user) => $user->roles->pluck('id')`) and `DefaultSubjectResolver` now emits a `Subject(Role, …)` per returned id, so `role` permission grants resolve out of the box without shipping a custom `LibrarySubjectResolver`. Ids are cast to strings and matched against `FolderPermission.subject_value`.
- The Filament ACL relation managers (v3/v4/v5) automatically re-enable the `role` subject-type option when a role source is configured (via `RoleSubjectSupport::enabled()`), reversing the hide when roles are wired.

### Notes

- Fully backward compatible: with no `roles.resolver` configured, behaviour is unchanged — role grants stay inert and the `role` option stays hidden.
- A closure in `roles.resolver` cannot survive `php artisan config:cache`; cache-config apps should bind a custom `subject_resolver` class instead.
- The additive ACL resolution semantics are unchanged.

## [3.1.0] - 2026-05-30

### Added

- Filament admin resources for **Filament v3, v4, and v5** in parallel, registered through a single version-dispatching `Kurt\Modules\ResourceLibrary\Filament\ResourceLibraryPlugin::make()` facade that resolves the matching `V3`/`V4`/`V5` plugin from the installed Filament major.
  - `FolderResource` — per-locale (en/tr) translatable name/description, parent (tree) select, visibility enum select; table with name/path/visibility badge/item count + visibility filter; ACL relation manager for `resource_library_folder_permissions`.
  - `ItemResource` — translatable title/description, reactive `kind` select (conditional `external_url` for video-link/external-URL kinds, Spatie media-library upload for file/document kinds), folder + tag selects, `published_at`; table with kind + published filters; read-only versions relation manager.
  - `TagResource` — translatable name with colour picker and colour swatch column.
  - `AccessLogResource` — read-only (no create/edit): item/user/action badge/timestamp table, filterable by action and date range, with a view action.
- `filament/spatie-laravel-media-library-plugin` (`^3.0 || ^4.0 || ^5.0`) require-dev for the item file/document upload field.
- Per-Filament-version PHPStan configs (`phpstan-filament-v{3,4,5}.neon`); the base `phpstan.neon` excludes all three version dirs + the facade and each per-version config adds its dir back.
- Version-guarded Filament resource smoke tests under `tests/Feature/Filament/V{3,4,5}/`; only the installed major's suite runs.
- CI matrix gains a Filament axis (`3.*`, `4.*`, `5.*`); PHPStan runs the per-major config in each cell.

## [3.0.1] - 2026-05-30

### Fixed
- Migrations now publish correctly via `vendor:publish --tag=modules-resource-library-migrations`. The previous bare-name `hasMigrations()` list pointed at non-existent source paths (real files are timestamp-prefixed). Switched to `discoversMigrations()`.

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
