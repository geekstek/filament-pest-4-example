<?php

use Acme\FilamentPosts\Filament\Resources\CommentResource;
use Acme\FilamentPosts\Filament\Resources\CommentResource\Pages\CreateComment;
use Acme\FilamentPosts\Filament\Resources\CommentResource\Pages\EditComment;
use Acme\FilamentPosts\Filament\Resources\CommentResource\Pages\ListComments;
use Acme\FilamentPosts\Models\Comment;
use Acme\FilamentPosts\Models\Post;
use Acme\FilamentPosts\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('CommentResource', function () {
    it('can render the index page', function () {
        livewire(ListComments::class)
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        livewire(CreateComment::class)
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $comment = Comment::factory()->create();

        livewire(EditComment::class, ['record' => $comment->getRouteKey()])
            ->assertSuccessful();
    });

    it('can list comments', function () {
        $comments = Comment::factory()->count(3)->create();

        livewire(ListComments::class)
            ->assertCanSeeTableRecords($comments);
    });

    it('can create a comment', function () {
        $post = Post::factory()->create();

        $newData = [
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'This is a test comment.',
            'is_approved' => false,
        ];

        livewire(CreateComment::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Comment::class, [
            'author_name' => $newData['author_name'],
            'author_email' => $newData['author_email'],
            'post_id' => $post->id,
        ]);
    });

    it('can update a comment', function () {
        $comment = Comment::factory()->create();

        $newData = [
            'author_name' => 'Jane Doe',
            'content' => 'Updated content',
        ];

        livewire(EditComment::class, ['record' => $comment->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $comment->refresh();

        expect($comment->author_name)->toBe($newData['author_name'])
            ->and($comment->content)->toBe($newData['content']);
    });

    it('can delete a comment', function () {
        $comment = Comment::factory()->create();

        livewire(EditComment::class, ['record' => $comment->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($comment);
    });

    it('can approve a comment via table action', function () {
        $comment = Comment::factory()->pending()->create();

        livewire(ListComments::class)
            ->callTableAction('approve', $comment);

        $comment->refresh();
        expect($comment->is_approved)->toBeTrue();
    });

    it('can reject a comment via table action', function () {
        $comment = Comment::factory()->approved()->create();

        livewire(ListComments::class)
            ->callTableAction('reject', $comment);

        $comment->refresh();
        expect($comment->is_approved)->toBeFalse();
    });

    it('can bulk approve comments', function () {
        $comments = Comment::factory()->pending()->count(3)->create();

        livewire(ListComments::class)
            ->callTableBulkAction('approve', $comments);

        foreach ($comments as $comment) {
            $comment->refresh();
            expect($comment->is_approved)->toBeTrue();
        }
    });

    it('can bulk reject comments', function () {
        $comments = Comment::factory()->approved()->count(3)->create();

        livewire(ListComments::class)
            ->callTableBulkAction('reject', $comments);

        foreach ($comments as $comment) {
            $comment->refresh();
            expect($comment->is_approved)->toBeFalse();
        }
    });

    it('can filter by approval status', function () {
        $approvedComment = Comment::factory()->approved()->create();
        $pendingComment = Comment::factory()->pending()->create();

        livewire(ListComments::class)
            ->filterTable('is_approved', true)
            ->assertCanSeeTableRecords([$approvedComment])
            ->assertCanNotSeeTableRecords([$pendingComment]);
    });

    it('can search comments by author name', function () {
        $matchingComment = Comment::factory()->create(['author_name' => 'John Smith']);
        $nonMatchingComment = Comment::factory()->create(['author_name' => 'Jane Doe']);

        livewire(ListComments::class)
            ->searchTable('John Smith')
            ->assertCanSeeTableRecords([$matchingComment])
            ->assertCanNotSeeTableRecords([$nonMatchingComment]);
    });

    it('has tabs for filtering comments', function () {
        $pendingComment = Comment::factory()->pending()->create();
        $approvedComment = Comment::factory()->approved()->create();

        livewire(ListComments::class)
            ->assertSuccessful();

        livewire(ListComments::class)
            ->set('activeTab', 'pending')
            ->assertCanSeeTableRecords([$pendingComment])
            ->assertCanNotSeeTableRecords([$approvedComment]);

        livewire(ListComments::class)
            ->set('activeTab', 'approved')
            ->assertCanSeeTableRecords([$approvedComment])
            ->assertCanNotSeeTableRecords([$pendingComment]);
    });

    it('validates required fields on create', function () {
        livewire(CreateComment::class)
            ->fillForm([
                'post_id' => null,
                'author_name' => '',
                'author_email' => '',
                'content' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['post_id', 'author_name', 'author_email', 'content']);
    });

    it('validates email format', function () {
        $post = Post::factory()->create();

        livewire(CreateComment::class)
            ->fillForm([
                'post_id' => $post->id,
                'author_name' => 'John',
                'author_email' => 'invalid-email',
                'content' => 'Test content',
            ])
            ->call('create')
            ->assertHasFormErrors(['author_email']);
    });

    it('can access resource via URL', function () {
        $this->get(CommentResource::getUrl('index'))
            ->assertSuccessful();

        $this->get(CommentResource::getUrl('create'))
            ->assertSuccessful();

        $comment = Comment::factory()->create();
        $this->get(CommentResource::getUrl('edit', ['record' => $comment]))
            ->assertSuccessful();
    });
});
