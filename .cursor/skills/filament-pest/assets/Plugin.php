<?php

// Template: Filament 4 Plugin
// Replace: VendorName\PackageName, resource/page class references

namespace VendorName\PackageName;

use Filament\Contracts\Plugin;
use Filament\Panel;

class PackagePlugin implements Plugin
{
    public function getId(): string
    {
        return 'package-name';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                // Filament\Resources\ExampleResource::class,
            ])
            ->pages([
                // Filament\Pages\Dashboard::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
