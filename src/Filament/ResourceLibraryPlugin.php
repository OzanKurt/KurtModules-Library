<?php

declare(strict_types=1);

namespace Kurt\Modules\ResourceLibrary\Filament;

use Filament\Contracts\Plugin;
use Kurt\Modules\Core\Support\FilamentVersion;

/**
 * Version-dispatching facade for the ResourceLibrary Filament plugin.
 *
 * Register on a panel with `->plugin(\Kurt\Modules\ResourceLibrary\Filament\ResourceLibraryPlugin::make())`.
 * The correct V{n} plugin is resolved from the installed Filament major, so the
 * same call works whether the consumer runs Filament 3, 4, or 5.
 */
final class ResourceLibraryPlugin
{
    public static function make(): Plugin
    {
        return match (FilamentVersion::major()) {
            5 => new V5\ResourceLibraryPlugin,
            4 => new V4\ResourceLibraryPlugin,
            3 => new V3\ResourceLibraryPlugin,
            default => throw new \RuntimeException('Filament is not installed; cannot register the ResourceLibrary plugin.'),
        };
    }
}
