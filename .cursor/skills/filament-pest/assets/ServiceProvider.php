<?php

// Template: ServiceProvider using spatie/laravel-package-tools
// Replace: VendorName\PackageName, package-name, migration names

namespace VendorName\PackageName;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PackageServiceProvider extends PackageServiceProvider
{
    public static string $name = 'package-name';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            // Register each migration (use .php extension, NOT .php.stub)
            ->hasMigration('create_example_table')
            ->runsMigrations();
    }
}
