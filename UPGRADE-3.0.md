# Upgrade Guide — v2.x → v3.0

The 3.0 release is a rename: composer name, namespace, config key, console signatures, and table prefix all change. No functional behavior changes.

## 1. Composer

```diff
-"ozankurt/laravel-modules-library": "^2.0"
+"ozankurt/laravel-modules-resource-library": "^3.0"
```

Run `composer update`.

## 2. PHP namespace

Find-and-replace across your `app/` directory:

| Find | Replace |
|---|---|
| `Kurt\Modules\Library\` | `Kurt\Modules\ResourceLibrary\` |
| `Kurt\\Modules\\Library\\` | `Kurt\\Modules\\ResourceLibrary\\` |

(VS Code regex: `\bKurt\\Modules\\Library\b` → `Kurt\Modules\ResourceLibrary`.)

## 3. Config key

```diff
-config('library.media.disk')
+config('resource-library.media.disk')
```

If you've published the config file:

```bash
mv config/library.php config/resource-library.php
```

If you've overridden the media disk env var, rename it as well:

```diff
-LIBRARY_MEDIA_DISK=s3
+RESOURCE_LIBRARY_MEDIA_DISK=s3
```

## 4. Artisan commands

| Old | New |
|---|---|
| `library:recount` | `resource-library:recount` |
| `library:prune-versions` | `resource-library:prune-versions` |
| `library:rebuild-paths` | `resource-library:rebuild-paths` |
| `library:demo` | `resource-library:demo` |

Update any cron entries and scheduler bindings.

## 5. Database tables

Run pending migrations:

```bash
php artisan migrate
```

The `2026_05_30_000100_rename_library_to_resource_library` migration renames every `library_*` table to `resource_library_*`. Existing data is preserved.

For new installations, fresh installs create the `resource_library_*` tables directly.

> **Note:** The rename migration's `down()` is intentionally a no-op. For a rollback in production, restore from a database backup rather than relying on `migrate:rollback`.

## 6. Service provider

If your app's `config/app.php` lists providers manually:

```diff
-Kurt\Modules\Library\Providers\LibraryServiceProvider::class,
+Kurt\Modules\ResourceLibrary\Providers\ResourceLibraryServiceProvider::class,
```

If you rely on Laravel's package auto-discovery, no change needed.

## 7. Facades

If you import the facade (none ships in v3 by default, but if you wrote one):

```diff
-use Kurt\Modules\Library\Facades\Library;
+use Kurt\Modules\ResourceLibrary\Facades\ResourceLibrary;

-Library::createFolder(...);
+ResourceLibrary::createFolder(...);
```

## 8. Verify

After upgrade:

```bash
php artisan migrate:status   # confirms the rename migration ran
php artisan resource-library:demo   # smoke test
```
