<?php

declare(strict_types=1);

namespace Kurt\Modules\Library\Providers;

use Illuminate\Contracts\Auth\Access\Gate;
use Kurt\Modules\Core\Providers\PackageServiceProvider;
use Kurt\Modules\Library\Access\LibraryAccess;
use Kurt\Modules\Library\Access\PermissionResolver;
use Kurt\Modules\Library\Console\Commands\DemoCommand;
use Kurt\Modules\Library\Console\Commands\PruneVersionsCommand;
use Kurt\Modules\Library\Console\Commands\RebuildPathsCommand;
use Kurt\Modules\Library\Console\Commands\RecountCommand;
use Kurt\Modules\Library\Contracts\LibrarySubjectResolver;
use Kurt\Modules\Library\Models\Folder;
use Kurt\Modules\Library\Models\Item;
use Kurt\Modules\Library\Models\ItemVersion;
use Kurt\Modules\Library\Observers\FolderObserver;
use Kurt\Modules\Library\Observers\ItemObserver;
use Kurt\Modules\Library\Observers\ItemVersionObserver;
use Kurt\Modules\Library\Policies\FolderPolicy;
use Kurt\Modules\Library\Policies\ItemPolicy;
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
