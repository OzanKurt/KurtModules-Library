<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Event;
use Kurt\Modules\Core\Modules\ModuleManifest;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Access\ResourceLibraryAccess;
use Kurt\Modules\ResourceLibrary\Console\Commands\DemoCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\PruneVersionsCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\RebuildPathsCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\RecountCommand;
use Kurt\Modules\ResourceLibrary\Contracts\ResourceLibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Events\FolderMoved;
use Kurt\Modules\ResourceLibrary\Events\FolderPermissionChanged;
use Kurt\Modules\ResourceLibrary\Listeners\BumpAclCache;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\FolderPermission;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;
use Kurt\Modules\ResourceLibrary\Observers\FolderObserver;
use Kurt\Modules\ResourceLibrary\Observers\FolderPermissionObserver;
use Kurt\Modules\ResourceLibrary\Observers\ItemObserver;
use Kurt\Modules\ResourceLibrary\Observers\ItemVersionObserver;
use Kurt\Modules\ResourceLibrary\Policies\FolderPolicy;
use Kurt\Modules\ResourceLibrary\Policies\ItemPolicy;
use Spatie\LaravelPackageTools\Package;

final class ResourceLibraryServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'resource-library';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-resource-library')
            ->hasConfigFile('resource-library')
            ->hasTranslations()
            ->discoversMigrations()
            ->hasCommands([
                RecountCommand::class,
                PruneVersionsCommand::class,
                RebuildPathsCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ResourceLibrarySubjectResolver::class, function () {
            /** @var class-string<ResourceLibrarySubjectResolver> $class */
            $class = (string) config('resource-library.subject_resolver');

            return $this->app->make($class);
        });

        $this->app->singleton(PermissionResolver::class);
        $this->app->scoped(ResourceLibraryAccess::class);
    }

    protected function moduleManifest(): ?ModuleManifest
    {
        return ModuleManifest::make('resource-library')
            ->name('Resource Library')
            ->description('SaaS resource library: nested folders with per-folder permissions, versioned items (video link, file, document, URL).');
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        Folder::observe(FolderObserver::class);
        FolderPermission::observe(FolderPermissionObserver::class);
        Item::observe(ItemObserver::class);
        ItemVersion::observe(ItemVersionObserver::class);

        // Cross-request ACL cache invalidation. A permission edit or a folder
        // move (whose subtree ancestry shifts) bumps the whole `acl` scope, so a
        // revoked grant or a moved folder can never be served from a stale
        // cached capability.
        Event::listen(FolderPermissionChanged::class, BumpAclCache::class);
        Event::listen(FolderMoved::class, BumpAclCache::class);

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Folder::class, FolderPolicy::class);
        $gate->policy(Item::class, ItemPolicy::class);

        // Register the out-of-the-box REST API. A no-op in headless mode; when
        // enabled it wires the module's rate limiter + route group. Every route
        // enforces the per-folder ACL inside its controller.
        $this->registerModuleApi(__DIR__.'/../../routes/api.php');
    }
}
