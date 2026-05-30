<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament\V3;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\AccessLogResource;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\FolderResource;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\ItemResource;
use Kurt\Modules\ResourceLibrary\Filament\V3\Resources\TagResource;

final class ResourceLibraryPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-resource-library';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            FolderResource::class,
            ItemResource::class,
            TagResource::class,
            AccessLogResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
