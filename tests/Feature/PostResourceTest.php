<?php

use Acme\FilamentPosts\Filament\Resources\PostResource;
use Acme\FilamentPosts\Filament\Resources\PostResource\Pages\CreatePost;
use Acme\FilamentPosts\Filament\Resources\PostResource\Pages\EditPost;
use Acme\FilamentPosts\Filament\Resources\PostResource\Pages\ListPosts;
use Acme\FilamentPosts\Filament\Resources\PostResource\RelationManagers\CommentsRelationManager;
use Acme\FilamentPosts\Filament\Resources\PostResource\RelationManagers\TagsRelationManager;
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

describe('PostResource', function () {
    it('can render the index page', function () {
        livewire(ListPosts::class)
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        livewire(CreatePost::class)
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $post = Post::factory()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertSuccessful();
    });

    it('can list posts', function () {
        $posts = Post::factory()->count(3)->create();

        livewire(ListPosts::class)
            ->assertCanSeeTableRecords($posts);
    });

    it('can create a post', function () {
        $newData = [
            'title' => 'Test Post Title',
            'slug' => 'test-post-title',
            'content' => 'This is the content of the test post.',
            'is_published' => true,
        ];

        livewire(CreatePost::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Post::class, [
            'title' => $newData['title'],
            'slug' => $newData['slug'],
            'is_published' => true,
        ]);
    });

    it('can create a post with category', function () {
        $category = Category::factory()->create();

        $newData = [
            'title' => 'Post with Category',
            'slug' => 'post-with-category',
            'category_id' => $category->id,
            'is_published' => false,
        ];

        livewire(CreatePost::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Post::class, [
            'title' => $newData['title'],
            'category_id' => $category->id,
        ]);
    });

    it('can create a post with tags', function () {
        $tags = Tag::factory()->count(3)->create();

        $newData = [
            'title' => 'Post with Tags',
            'slug' => 'post-with-tags',
            'tags' => $tags->pluck('id')->toArray(),
            'is_published' => false,
        ];

        livewire(CreatePost::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'post-with-tags')->first();
        expect($post->tags)->toHaveCount(3);
    });

    it('can update a post', function () {
        $post = Post::factory()->create();

        $newData = [
            'title' => 'Updated Post Title',
            'slug' => 'updated-post-title',
        ];

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        expect($post->title)->toBe($newData['title'])
            ->and($post->slug)->toBe($newData['slug']);
    });

    it('can delete a post', function () {
        $post = Post::factory()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($post);
    });

    it('can delete posts in bulk', function () {
        $posts = Post::factory()->count(3)->create();

        livewire(ListPosts::class)
            ->callTableBulkAction('delete', $posts);

        foreach ($posts as $post) {
            $this->assertModelMissing($post);
        }
    });

    it('can search posts by title', function () {
        $matchingPost = Post::factory()->create(['title' => 'Searchable Post']);
        $nonMatchingPost = Post::factory()->create(['title' => 'Another Article']);

        livewire(ListPosts::class)
            ->searchTable('Searchable')
            ->assertCanSeeTableRecords([$matchingPost])
            ->assertCanNotSeeTableRecords([$nonMatchingPost]);
    });

    it('can sort posts by title', function () {
        $posts = Post::factory()->count(3)->create();

        livewire(ListPosts::class)
            ->sortTable('title')
            ->assertCanSeeTableRecords($posts->sortBy('title'), inOrder: true);
    });

    it('can filter posts by published status', function () {
        $publishedPost = Post::factory()->published()->create();
        $draftPost = Post::factory()->draft()->create();

        livewire(ListPosts::class)
            ->filterTable('is_published', true)
            ->assertCanSeeTableRecords([$publishedPost])
            ->assertCanNotSeeTableRecords([$draftPost]);
    });

    it('can filter posts by featured status', function () {
        $featuredPost = Post::factory()->featured()->create();
        $normalPost = Post::factory()->create(['is_featured' => false]);

        livewire(ListPosts::class)
            ->filterTable('is_featured', true)
            ->assertCanSeeTableRecords([$featuredPost])
            ->assertCanNotSeeTableRecords([$normalPost]);
    });

    it('can filter posts by category', function () {
        $category = Category::factory()->create();
        $postWithCategory = Post::factory()->withCategory($category)->create();
        $postWithoutCategory = Post::factory()->create();

        livewire(ListPosts::class)
            ->filterTable('category', $category->id)
            ->assertCanSeeTableRecords([$postWithCategory])
            ->assertCanNotSeeTableRecords([$postWithoutCategory]);
    });

    it('validates required fields on create', function () {
        livewire(CreatePost::class)
            ->fillForm([
                'title' => '',
                'slug' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'slug' => 'required']);
    });

    it('validates unique slug', function () {
        Post::factory()->create(['slug' => 'existing-slug']);

        livewire(CreatePost::class)
            ->fillForm([
                'title' => 'New Post',
                'slug' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });

    it('has correct form schema', function () {
        livewire(CreatePost::class)
            ->assertFormFieldExists('title')
            ->assertFormFieldExists('slug')
            ->assertFormFieldExists('content')
            ->assertFormFieldExists('category_id')
            ->assertFormFieldExists('tags')
            ->assertFormFieldExists('is_published')
            ->assertFormFieldExists('is_featured')
            ->assertFormFieldExists('published_at');
    });

    it('has correct table columns', function () {
        livewire(ListPosts::class)
            ->assertTableColumnExists('title')
            ->assertTableColumnExists('slug')
            ->assertTableColumnExists('category.name')
            ->assertTableColumnExists('is_published')
            ->assertTableColumnExists('is_featured')
            ->assertTableColumnExists('comments_count')
            ->assertTableColumnExists('published_at');
    });

    it('auto-generates slug from title', function () {
        livewire(CreatePost::class)
            ->fillForm([
                'title' => 'My Awesome Post',
            ])
            ->assertFormSet([
                'slug' => 'my-awesome-post',
            ]);
    });

    it('can access resource via URL', function () {
        $this->get(PostResource::getUrl('index'))
            ->assertSuccessful();

        $this->get(PostResource::getUrl('create'))
            ->assertSuccessful();

        $post = Post::factory()->create();
        $this->get(PostResource::getUrl('edit', ['record' => $post]))
            ->assertSuccessful();
    });
});

describe('PostResource Table Actions', function () {
    it('can publish a post via table action', function () {
        $post = Post::factory()->draft()->create();

        livewire(ListPosts::class)
            ->callTableAction('publish', $post);

        $post->refresh();
        expect($post->is_published)->toBeTrue()
            ->and($post->published_at)->not->toBeNull();
    });

    it('can toggle feature status via table action', function () {
        $post = Post::factory()->create(['is_featured' => false]);

        livewire(ListPosts::class)
            ->callTableAction('feature', $post);

        $post->refresh();
        expect($post->is_featured)->toBeTrue();

        livewire(ListPosts::class)
            ->callTableAction('feature', $post);

        $post->refresh();
        expect($post->is_featured)->toBeFalse();
    });

    it('can bulk publish posts', function () {
        $posts = Post::factory()->draft()->count(3)->create();

        livewire(ListPosts::class)
            ->callTableBulkAction('publish', $posts);

        foreach ($posts as $post) {
            $post->refresh();
            expect($post->is_published)->toBeTrue();
        }
    });

    it('can bulk feature posts', function () {
        $posts = Post::factory()->count(3)->create(['is_featured' => false]);

        livewire(ListPosts::class)
            ->callTableBulkAction('feature', $posts);

        foreach ($posts as $post) {
            $post->refresh();
            expect($post->is_featured)->toBeTrue();
        }
    });
});

describe('PostResource Header Actions', function () {
    it('can publish a post via header action', function () {
        $post = Post::factory()->draft()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction('publish_post');

        $post->refresh();
        expect($post->is_published)->toBeTrue();
    });

    it('can archive a post via header action', function () {
        $post = Post::factory()->published()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->callAction('archive_post');

        $post->refresh();
        expect($post->is_published)->toBeFalse();
    });

    it('publish action is hidden when post is already published', function () {
        $post = Post::factory()->published()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertActionHidden('publish_post');
    });

    it('archive action is hidden when post is draft', function () {
        $post = Post::factory()->draft()->create();

        livewire(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertActionHidden('archive_post');
    });
});

describe('PostResource CommentsRelationManager', function () {
    it('can render the comments relation manager', function () {
        $post = Post::factory()->create();

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertSuccessful();
    });

    it('can list related comments', function () {
        $post = Post::factory()->create();
        $comments = Comment::factory()->count(3)->create(['post_id' => $post->id]);

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertCanSeeTableRecords($comments);
    });

    it('can create a comment from relation manager', function () {
        $post = Post::factory()->create();

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('create', data: [
                'author_name' => 'Test Author',
                'author_email' => 'test@example.com',
                'content' => 'Test comment content',
                'is_approved' => false,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas(Comment::class, [
            'author_name' => 'Test Author',
            'post_id' => $post->id,
        ]);
    });

    it('can approve a comment from relation manager', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->pending()->create(['post_id' => $post->id]);

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('approve', $comment);

        $comment->refresh();
        expect($comment->is_approved)->toBeTrue();
    });

    it('can bulk approve comments from relation manager', function () {
        $post = Post::factory()->create();
        $comments = Comment::factory()->pending()->count(3)->create(['post_id' => $post->id]);

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableBulkAction('approve', $comments);

        foreach ($comments as $comment) {
            $comment->refresh();
            expect($comment->is_approved)->toBeTrue();
        }
    });

    it('can delete a comment from relation manager', function () {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        livewire(CommentsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('delete', $comment);

        $this->assertModelMissing($comment);
    });
});

describe('PostResource TagsRelationManager', function () {
    it('can render the tags relation manager', function () {
        $post = Post::factory()->create();

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertSuccessful();
    });

    it('can list related tags', function () {
        $post = Post::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $post->tags()->attach($tags);

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->assertCanSeeTableRecords($tags);
    });

    it('can create a tag from relation manager', function () {
        $post = Post::factory()->create();

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'New Tag',
                'slug' => 'new-tag',
                'color' => '#FF0000',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas(Tag::class, [
            'name' => 'New Tag',
            'slug' => 'new-tag',
        ]);

        $post->refresh();
        expect($post->tags)->toHaveCount(1);
    });

    it('can attach existing tag to post', function () {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $tag->id,
            ])
            ->assertHasNoTableActionErrors();

        $post->refresh();
        expect($post->tags)->toHaveCount(1)
            ->and($post->tags->first()->id)->toBe($tag->id);
    });

    it('can detach tag from post', function () {
        $post = Post::factory()->create();
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableAction('detach', $tag);

        $post->refresh();
        expect($post->tags)->toHaveCount(0);
    });

    it('can bulk detach tags from post', function () {
        $post = Post::factory()->create();
        $tags = Tag::factory()->count(3)->create();
        $post->tags()->attach($tags);

        livewire(TagsRelationManager::class, [
            'ownerRecord' => $post,
            'pageClass' => EditPost::class,
        ])
            ->callTableBulkAction('detach', $tags);

        $post->refresh();
        expect($post->tags)->toHaveCount(0);
    });
});
