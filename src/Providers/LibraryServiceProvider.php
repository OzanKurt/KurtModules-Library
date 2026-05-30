<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\ResourceLibrary\Access\LibraryAccess;
use Kurt\Modules\ResourceLibrary\Access\PermissionResolver;
use Kurt\Modules\ResourceLibrary\Console\Commands\DemoCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\PruneVersionsCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\RebuildPathsCommand;
use Kurt\Modules\ResourceLibrary\Console\Commands\RecountCommand;
use Kurt\Modules\ResourceLibrary\Contracts\LibrarySubjectResolver;
use Kurt\Modules\ResourceLibrary\Models\Folder;
use Kurt\Modules\ResourceLibrary\Models\Item;
use Kurt\Modules\ResourceLibrary\Models\ItemVersion;
use Kurt\Modules\ResourceLibrary\Observers\FolderObserver;
use Kurt\Modules\ResourceLibrary\Observers\ItemObserver;
use Kurt\Modules\ResourceLibrary\Observers\ItemVersionObserver;
use Kurt\Modules\ResourceLibrary\Policies\FolderPolicy;
use Kurt\Modules\ResourceLibrary\Policies\ItemPolicy;
use Spatie\LaravelPackageTools\Package;

final class LibraryServiceProvider extends PackageServiceProvider
{
    protected function module(): string
    {
        return 'library';
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-modules-library')
            ->hasConfigFile('library')
            ->hasTranslations()
            ->hasMigrations([
                'create_library_folders_table',
                'create_library_item_versions_table',
                'create_library_items_table',
                'create_library_tags_table',
                'create_library_item_tag_table',
                'create_library_folder_permissions_table',
                'create_library_access_log_table',
            ])
            ->hasCommands([
                RecountCommand::class,
                PruneVersionsCommand::class,
                RebuildPathsCommand::class,
                DemoCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(LibrarySubjectResolver::class, function () {
            /** @var class-string<LibrarySubjectResolver> $class */
            $class = (string) config('library.subject_resolver');

            return $this->app->make($class);
        });

        $this->app->singleton(PermissionResolver::class);
        $this->app->scoped(LibraryAccess::class);
    }

    public function packageBooted(): void
    {
        Folder::observe(FolderObserver::class);
        Item::observe(ItemObserver::class);
        ItemVersion::observe(ItemVersionObserver::class);

        /** @var Gate $gate */
        $gate = $this->app->make(Gate::class);
        $gate->policy(Folder::class, FolderPolicy::class);
        $gate->policy(Item::class, ItemPolicy::class);
    }
}
