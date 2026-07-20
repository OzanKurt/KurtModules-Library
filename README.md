# laravel-modules-resource-library

SaaS **resource library** module for Laravel: nested folders with per-folder permissions, versioned items, mixed kinds (video link, file, document, external URL), translatable content, Spatie medialibrary, access logging.

## v3.0 rename

This is the **v3.0 rename** of the previous `ozankurt/laravel-modules-library` v2 package. Composer name, PHP namespace, service provider, config key, console signatures, and table prefix all change from `library*` to `resource_library*`. No functional behavior changes. See [`UPGRADE-3.0.md`](./UPGRADE-3.0.md) for the consumer migration steps.

The rename disambiguates from the new `ozankurt/laravel-modules-media-library` package — a WordPress-style media bucket — which keeps the cleaner name.

## Requirements

- PHP 8.4+
- Laravel 12.x or 13.x
- `ozankurt/laravel-modules-core` v2.x

## Installation

```bash
composer require ozankurt/laravel-modules-resource-library
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=resource-library-config
php artisan vendor:publish --tag=resource-library-migrations
php artisan migrate
```

## Concepts

- **Folder** — node in a tree (self-referential `parent_id`). Denormalised `path` column for fast ancestry queries (bounded to 1024 chars; see [Max depth](#max-depth)). `visibility` controls fallback behaviour: `public` / `restricted` / `private`.
- **Item** — leaf in a folder. `kind` is one of `video_link`, `file`, `document`, `external_url`. Each kind stores its payload differently.
- **Version** — every mutation creates a new `ItemVersion` row. `current_version_id` points at the active one.
- **Permission** — per-folder ACL. Subject is `user`, `role`, or `everyone`. Grants `view`, `download`, or `manage`. Rows cascade to descendants by default. Resolution is **additive** (see [ACL resolution](#acl-resolution)). Note: `role` subjects only resolve once a role source is configured (a `resource-library.roles.resolver` callable) or the app ships a custom resolver (see [Subject resolver](#subject-resolver)).
- **Access log** — audit row written on `download` (and optionally `view`) of an item.

## What it provides

- Models: `Folder`, `Item`, `ItemVersion`, `Tag`, `FolderPermission`, `AccessLog`.
- Enums: `FolderVisibility`, `ItemKind`, `PermissionSubjectType`, `Capability`, `AccessAction`.
- Access service: `Kurt\Modules\ResourceLibrary\Access\LibraryAccess::check($user, Folder|Item, Capability)`.
- `Folder::moveTo(?Folder $newParent)` — rewrites a whole subtree's `path` + `depth` with a single anchored `UPDATE` (plus the moved folder's own row), independent of subtree size. Guards against cycles and destination slug collisions with a `CannotMoveFolderException`.
- Policies (`FolderPolicy`, `ItemPolicy`) that delegate to `LibraryAccess`. Global `canAdminLibrary` gate bypasses everything.
- Console commands: `resource-library:recount`, `resource-library:prune-versions`, `resource-library:rebuild-paths`, `resource-library:demo`.
- Domain events: `FolderCreated/Updated/Deleted/Moved`, `ItemCreated/Updated/Published/Unpublished/Deleted`, `ItemVersionCreated`, `ItemAccessed`, `TagCreated/Deleted`, `FolderPermissionChanged`.

## Subject resolver

Permission resolution maps the host application's identity model (user + roles) onto module subjects. The default resolver ships:

```php
[Subject(Everyone, null), Subject(User, (string) $user->getKey())]
```

### Wiring roles (the easy path)

To make `role` grants work **without writing a resolver class**, point the
default resolver at your app's role source with a callable at
`resource-library.roles.resolver`. It receives the current authenticatable and
returns that subject's role ids (an array, or an `Arrayable`/Collection of
`int|string`):

```php
// config/resource-library.php
'roles' => [
    'resolver' => fn ($user) => $user->roles->pluck('id'),
],
```

With this set, the default resolver additionally emits
`Subject(Role, (string) $roleId)` for each id, so a `role` permission row that
matches one of the user's roles resolves out of the box. The Filament ACL
relation managers detect the configured source and **re-enable the `role`
subject-type option** automatically.

Ids are cast to strings and matched against `FolderPermission.subject_value`, so
store role grants using the same id the resolver returns.

> A closure cannot survive `php artisan config:cache`. If you cache config, use
> a custom resolver class (below) instead of a closure here.

### Custom resolver (full control)

Apps that need more than a role-id list write a small `LibrarySubjectResolver`
and bind its FQCN via `config('resource-library.subject_resolver')`:

```php
final class AppSubjectResolver implements LibrarySubjectResolver
{
    public function subjects(?Authenticatable $user): array
    {
        $subjects = [new Subject(PermissionSubjectType::Everyone, null)];

        if ($user !== null) {
            $subjects[] = new Subject(PermissionSubjectType::User, (string) $user->getKey());

            foreach ($user->roles as $role) {
                $subjects[] = new Subject(PermissionSubjectType::Role, (string) $role->id);
            }
        }

        return $subjects;
    }
}
```

> **When neither is wired, `role` grants are inert.** The default resolver with
> no configured role source emits only `everyone` and `user` subjects, so a
> `role` permission row never matches. The Filament ACL relation managers
> reflect this honestly: they **hide the `role` subject-type option** (and flag
> it in a helper text) until a role source or custom resolver is configured, so
> admins are never offered a grant that silently does nothing. This keeps the
> package fully backward compatible: no config means no change in behaviour.

## ACL resolution

`LibraryAccess::check($user, Folder|Item, Capability)` resolves the effective
capability with an **additive, most-permissive-wins** model:

- The resolver walks the target folder **and its entire ancestor chain** and
  takes the **maximum** capability rank it can find (`view` < `download` <
  `manage`). It does **not** stop at the nearest matching ancestor.
- On the target folder itself, any matching row counts. On ancestors, only rows
  with `cascade = true` count.
- The folder's **visibility fallback** contributes its own baseline to the same
  maximum: `public` ⇒ `download`, `private` ⇒ `manage` for the owner,
  `restricted` ⇒ nothing. A `restricted` folder still inherits cascading
  ancestor grants; "restricted" only caps this fallback.

Because it takes the maximum across the whole chain, a broad, closer grant can
never **downgrade** a narrower, higher grant further up. For example, a user
granted `manage` on a grandparent keeps `manage` on a leaf even if the parent
carries a cascading `Everyone: view`.

> **Behaviour change (v3.1 → next):** earlier versions used "nearest-ancestor
> wins", where the closest matching ancestor's grant shadowed farther ones even
> when lower. That footgun is removed. If your app relied on a closer, lower
> grant to *cap* access inherited from higher up, that capping no longer
> happens — review your ACL rows before upgrading.

## Filament admin

The package ships parallel admin resource sets for Filament **v3, v4, and v5** —
`FolderResource`, `ItemResource`, `TagResource`, and `AccessLogResource`. The
correct set is chosen at runtime from the installed Filament major, so you
register a single version-dispatching plugin on your panel:

```php
use Filament\Panel;
use Kurt\Modules\ResourceLibrary\Filament\ResourceLibraryPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ResourceLibraryPlugin::make());
}
```

`ResourceLibraryPlugin::make()` resolves to the matching `V3`/`V4`/`V5` plugin
via `Kurt\Modules\Core\Support\FilamentVersion`. Install whichever Filament
major your app uses — items with file/document kinds use the Spatie media
library upload field:

```bash
# whichever your app runs
composer require filament/filament:"^3.0|^4.0|^5.0"
composer require filament/spatie-laravel-media-library-plugin:"^3.0|^4.0|^5.0"
```

What the resources give you:

- **Folders** — per-locale (en/tr) translatable name/description, a parent
  (tree) select and a `visibility` enum select; a table with name, path, a
  visibility badge and item count plus a visibility filter; and an
  **access-control relation manager** for the per-folder ACL
  (subject type/value, capability, cascade).
- **Items** — translatable title/description, a `kind` enum select that
  reactively shows an `external_url` field for video-link/external-URL kinds
  and a Spatie media-library upload for file/document kinds; folder and tag
  relationship selects; a `published_at` picker; a table with kind and
  published filters; and a read-only **versions relation manager** showing the
  version history.
- **Tags** — translatable name with a colour picker and a colour swatch column.
- **Access log** — **read-only** (no create/edit): a table of item, user,
  action badge and timestamp, filterable by action and date range, with a view
  action for the full audit row.

## Max depth

Ancestry is resolved from the denormalised `path` column (`/slug/slug/…`), which
is stored as `VARCHAR(1024)`. On MySQL/MariaDB the column is indexed with a
bounded 191-char prefix index (utf8mb4-safe) that is sufficient for the anchored
`path LIKE '/a/b/%'` scans; PostgreSQL indexes the full column; SQLite does not
enforce the length. In practice 1024 characters comfortably supports ~24 levels
of nesting at ~40-char slugs — deeper trees should use shorter slugs. The bound
is enforced by the `2026_07_20_000100_widen_resource_library_folder_path`
migration.

## License

MIT (c) Ozan Kurt
