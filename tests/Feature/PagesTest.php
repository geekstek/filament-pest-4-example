<?php

use Acme\FilamentPosts\Filament\Pages\Dashboard;
use Acme\FilamentPosts\Filament\Pages\Settings;
use Acme\FilamentPosts\Models\Category;
use Acme\FilamentPosts\Models\Comment;
use Acme\FilamentPosts\Models\Post;
use Acme\FilamentPosts\Models\Tag;
use Acme\FilamentPosts\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Dashboard Page', function () {
    it('can render the dashboard page', function () {
        livewire(Dashboard::class)
            ->assertSuccessful();
    });

    it('displays correct stats', function () {
        $draftPosts = Post::factory()->draft()->count(5)->create();
        $publishedPosts = Post::factory()->published()->count(3)->create();
        Category::factory()->count(4)->create();
        Tag::factory()->count(6)->create();
        Comment::factory()->pending()->count(2)->create();
        Comment::factory()->approved()->count(5)->create();

        $page = livewire(Dashboard::class);

        $stats = $page->instance()->getStats();

        $totalPosts = Post::count();
        $publishedCount = Post::where('is_published', true)->count();
        $categoryCount = Category::count();
        $tagCount = Tag::count();
        $pendingComments = Comment::where('is_approved', false)->count();

        expect($stats[0]['value'])->toBe($totalPosts)
            ->and($stats[0]['description'])->toBe($publishedCount . ' published')
            ->and($stats[1]['value'])->toBe($categoryCount)
            ->and($stats[2]['value'])->toBe($tagCount)
            ->and($stats[3]['value'])->toBe($pendingComments);
    });

    it('displays recent posts', function () {
        $posts = Post::factory()->count(10)->create();

        $page = livewire(Dashboard::class);
        $recentPosts = $page->instance()->getRecentPosts();

        expect($recentPosts)->toHaveCount(5);
    });

    it('displays pending comments', function () {
        Comment::factory()->pending()->count(10)->create();

        $page = livewire(Dashboard::class);
        $pendingComments = $page->instance()->getPendingComments();

        expect($pendingComments)->toHaveCount(5);
        $pendingComments->each(fn ($comment) => expect($comment->is_approved)->toBeFalse());
    });

    it('can access dashboard via URL', function () {
        $this->get('/admin/dashboard')
            ->assertSuccessful();
    });
});

describe('Settings Page', function () {
    it('can render the settings page', function () {
        livewire(Settings::class)
            ->assertSuccessful();
    });

    it('has correct form fields', function () {
        livewire(Settings::class)
            ->assertFormFieldExists('site_name')
            ->assertFormFieldExists('posts_per_page')
            ->assertFormFieldExists('allow_comments')
            ->assertFormFieldExists('moderate_comments');
    });

    it('loads default values on mount', function () {
        livewire(Settings::class)
            ->assertFormSet([
                'site_name' => 'My Blog',
                'posts_per_page' => 10,
                'allow_comments' => true,
                'moderate_comments' => true,
            ]);
    });

    it('can update settings', function () {
        livewire(Settings::class)
            ->fillForm([
                'site_name' => 'Updated Blog Name',
                'posts_per_page' => 20,
                'allow_comments' => false,
                'moderate_comments' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    });

    it('validates posts_per_page minimum value', function () {
        livewire(Settings::class)
            ->fillForm([
                'posts_per_page' => 0,
            ])
            ->call('save')
            ->assertHasFormErrors(['posts_per_page']);
    });

    it('validates posts_per_page maximum value', function () {
        livewire(Settings::class)
            ->fillForm([
                'posts_per_page' => 200,
            ])
            ->call('save')
            ->assertHasFormErrors(['posts_per_page']);
    });

    it('validates required site_name', function () {
        livewire(Settings::class)
            ->fillForm([
                'site_name' => '',
            ])
            ->call('save')
            ->assertHasFormErrors(['site_name' => 'required']);
    });

    it('can access settings via URL', function () {
        $this->get('/admin/settings')
            ->assertSuccessful();
    });
});
