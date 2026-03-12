<?php

namespace Acme\FilamentPosts\Filament\Pages;

use Acme\FilamentPosts\Models\Category;
use Acme\FilamentPosts\Models\Comment;
use Acme\FilamentPosts\Models\Post;
use Acme\FilamentPosts\Models\Tag;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class Dashboard extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected string $view = 'filament-posts::pages.dashboard';

    public function getStats(): array
    {
        return [
            [
                'label' => 'Total Posts',
                'value' => Post::count(),
                'description' => Post::where('is_published', true)->count() . ' published',
                'color' => 'primary',
            ],
            [
                'label' => 'Categories',
                'value' => Category::count(),
                'description' => Category::where('is_active', true)->count() . ' active',
                'color' => 'success',
            ],
            [
                'label' => 'Tags',
                'value' => Tag::count(),
                'description' => 'Used for organization',
                'color' => 'info',
            ],
            [
                'label' => 'Pending Comments',
                'value' => Comment::where('is_approved', false)->count(),
                'description' => Comment::where('is_approved', true)->count() . ' approved',
                'color' => 'warning',
            ],
        ];
    }

    public function getRecentPosts(): \Illuminate\Database\Eloquent\Collection
    {
        return Post::with('category')
            ->latest()
            ->take(5)
            ->get();
    }

    public function getPendingComments(): \Illuminate\Database\Eloquent\Collection
    {
        return Comment::with('post')
            ->where('is_approved', false)
            ->latest()
            ->take(5)
            ->get();
    }
}
