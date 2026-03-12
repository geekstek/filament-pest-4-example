<?php

namespace Acme\FilamentPosts;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPostsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-posts';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_categories_table')
            ->hasMigration('create_tags_table')
            ->hasMigration('create_posts_table')
            ->hasMigration('create_post_tag_table')
            ->hasMigration('create_comments_table')
            ->runsMigrations();
    }
}
