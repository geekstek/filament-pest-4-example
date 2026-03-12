<?php

namespace Acme\FilamentPosts;

use Acme\FilamentPosts\Filament\Pages\Dashboard;
use Acme\FilamentPosts\Filament\Pages\Settings;
use Acme\FilamentPosts\Filament\Resources\CategoryResource;
use Acme\FilamentPosts\Filament\Resources\CommentResource;
use Acme\FilamentPosts\Filament\Resources\PostResource;
use Acme\FilamentPosts\Filament\Resources\TagResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentPostsPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-posts';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                CategoryResource::class,
                PostResource::class,
                TagResource::class,
                CommentResource::class,
            ])
            ->pages([
                Dashboard::class,
                Settings::class,
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
