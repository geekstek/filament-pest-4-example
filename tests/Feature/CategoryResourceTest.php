<?php

use Acme\FilamentPosts\Filament\Resources\CategoryResource;
use Acme\FilamentPosts\Filament\Resources\CategoryResource\Pages\CreateCategory;
use Acme\FilamentPosts\Filament\Resources\CategoryResource\Pages\EditCategory;
use Acme\FilamentPosts\Filament\Resources\CategoryResource\Pages\ListCategories;
use Acme\FilamentPosts\Filament\Resources\CategoryResource\RelationManagers\PostsRelationManager;
use Acme\FilamentPosts\Models\Category;
use Acme\FilamentPosts\Models\Post;
use Acme\FilamentPosts\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('CategoryResource', function () {
    it('can render the index page', function () {
        livewire(ListCategories::class)
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        livewire(CreateCategory::class)
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $category = Category::factory()->create();

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful();
    });

    it('can list categories', function () {
        $categories = Category::factory()->count(3)->create();

        livewire(ListCategories::class)
            ->assertCanSeeTableRecords($categories);
    });

    it('can create a category', function () {
        $newData = [
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Technology related posts',
            'is_active' => true,
        ];

        livewire(CreateCategory::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Category::class, [
            'name' => $newData['name'],
            'slug' => $newData['slug'],
        ]);
    });

    it('can update a category', function () {
        $category = Category::factory()->create();

        $newData = [
            'name' => 'Updated Category',
            'slug' => 'updated-category',
        ];

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();

        expect($category->name)->toBe($newData['name'])
            ->and($category->slug)->toBe($newData['slug']);
    });

    it('can delete a category', function () {
        $category = Category::factory()->create();

        livewire(EditCategory::class, ['record' => $category->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($category);
    });

    it('can filter by active status', function () {
        $activeCategory = Category::factory()->create(['is_active' => true]);
        $inactiveCategory = Category::factory()->inactive()->create();

        livewire(ListCategories::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$activeCategory])
            ->assertCanNotSeeTableRecords([$inactiveCategory]);
    });

    it('can search categories by name', function () {
        $matchingCategory = Category::factory()->create(['name' => 'Technology']);
        $nonMatchingCategory = Category::factory()->create(['name' => 'Sports']);

        livewire(ListCategories::class)
            ->searchTable('Technology')
            ->assertCanSeeTableRecords([$matchingCategory])
            ->assertCanNotSeeTableRecords([$nonMatchingCategory]);
    });

    it('auto-generates slug from name', function () {
        livewire(CreateCategory::class)
            ->fillForm([
                'name' => 'My New Category',
            ])
            ->assertFormSet([
                'slug' => 'my-new-category',
            ]);
    });

    it('displays posts count in table', function () {
        $category = Category::factory()->create();
        Post::factory()->count(5)->create(['category_id' => $category->id]);

        livewire(ListCategories::class)
            ->assertTableColumnExists('posts_count');
    });

    it('can access resource via URL', function () {
        $this->get(CategoryResource::getUrl('index'))
            ->assertSuccessful();

        $this->get(CategoryResource::getUrl('create'))
            ->assertSuccessful();

        $category = Category::factory()->create();
        $this->get(CategoryResource::getUrl('edit', ['record' => $category]))
            ->assertSuccessful();
    });
});

describe('CategoryResource PostsRelationManager', function () {
    it('can render the relation manager', function () {
        $category = Category::factory()->create();

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->assertSuccessful();
    });

    it('can list related posts', function () {
        $category = Category::factory()->create();
        $posts = Post::factory()->count(3)->create(['category_id' => $category->id]);

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->assertCanSeeTableRecords($posts);
    });

    it('can create a post from relation manager', function () {
        $category = Category::factory()->create();

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->callTableAction('create', data: [
                'title' => 'New Post',
                'slug' => 'new-post',
                'is_published' => false,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas(Post::class, [
            'title' => 'New Post',
            'category_id' => $category->id,
        ]);
    });

    it('can edit a post from relation manager', function () {
        $category = Category::factory()->create();
        $post = Post::factory()->create(['category_id' => $category->id]);

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->callTableAction('edit', $post, data: [
                'title' => 'Updated Title',
                'slug' => 'updated-title',
            ])
            ->assertHasNoTableActionErrors();

        $post->refresh();
        expect($post->title)->toBe('Updated Title');
    });

    it('can delete a post from relation manager', function () {
        $category = Category::factory()->create();
        $post = Post::factory()->create(['category_id' => $category->id]);

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->callTableAction('delete', $post);

        $this->assertModelMissing($post);
    });

    it('can publish a post from relation manager', function () {
        $category = Category::factory()->create();
        $post = Post::factory()->draft()->create(['category_id' => $category->id]);

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->callTableAction('publish', $post);

        $post->refresh();
        expect($post->is_published)->toBeTrue();
    });

    it('can filter posts by published status', function () {
        $category = Category::factory()->create();
        $publishedPost = Post::factory()->published()->create(['category_id' => $category->id]);
        $draftPost = Post::factory()->draft()->create(['category_id' => $category->id]);

        livewire(PostsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => EditCategory::class,
        ])
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$publishedPost])
            ->assertCanNotSeeTableRecords([$draftPost]);
    });
});
