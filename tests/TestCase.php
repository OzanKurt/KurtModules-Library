<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Tests;

use Cviebrock\EloquentSluggable\ServiceProvider as SluggableServiceProvider;
use Illuminate\Foundation\Application;
use Kurt\Modules\Core\Providers\CoreServiceProvider;
use Kurt\Modules\Core\Testing\PackageTestCase;
use Kurt\Modules\ResourceLibrary\Providers\ResourceLibraryServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends PackageTestCase
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function modulePackageProviders($app): array
    {
        return [
            SluggableServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ResourceLibraryServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
            SluggableServiceProvider::class,
            MediaLibraryServiceProvider::class,
            ResourceLibraryServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        $this->loadMigrationsFrom(__DIR__.'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
