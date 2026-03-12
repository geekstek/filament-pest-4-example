<?php

use Acme\FilamentPosts\Filament\Resources\TagResource;
use Acme\FilamentPosts\Filament\Resources\TagResource\Pages\CreateTag;
use Acme\FilamentPosts\Filament\Resources\TagResource\Pages\EditTag;
use Acme\FilamentPosts\Filament\Resources\TagResource\Pages\ListTags;
use Acme\FilamentPosts\Models\Post;
use Acme\FilamentPosts\Models\Tag;
use Acme\FilamentPosts\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('TagResource', function () {
    it('can render the index page', function () {
        livewire(ListTags::class)
            ->assertSuccessful();
    });

    it('can render the create page', function () {
        livewire(CreateTag::class)
            ->assertSuccessful();
    });

    it('can render the edit page', function () {
        $tag = Tag::factory()->create();

        livewire(EditTag::class, ['record' => $tag->getRouteKey()])
            ->assertSuccessful();
    });

    it('can list tags', function () {
        $tags = Tag::factory()->count(3)->create();

        livewire(ListTags::class)
            ->assertCanSeeTableRecords($tags);
    });

    it('can create a tag', function () {
        $newData = [
            'name' => 'Laravel',
            'slug' => 'laravel',
            'color' => '#FF5733',
        ];

        livewire(CreateTag::class)
            ->fillForm($newData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Tag::class, [
            'name' => $newData['name'],
            'slug' => $newData['slug'],
        ]);
    });

    it('can update a tag', function () {
        $tag = Tag::factory()->create();

        $newData = [
            'name' => 'Updated Tag',
            'slug' => 'updated-tag',
            'color' => '#00FF00',
        ];

        livewire(EditTag::class, ['record' => $tag->getRouteKey()])
            ->fillForm($newData)
            ->call('save')
            ->assertHasNoFormErrors();

        $tag->refresh();

        expect($tag->name)->toBe($newData['name'])
            ->and($tag->slug)->toBe($newData['slug']);
    });

    it('can delete a tag', function () {
        $tag = Tag::factory()->create();

        livewire(EditTag::class, ['record' => $tag->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($tag);
    });

    it('can search tags by name', function () {
        $matchingTag = Tag::factory()->create(['name' => 'Laravel']);
        $nonMatchingTag = Tag::factory()->create(['name' => 'Vue']);

        livewire(ListTags::class)
            ->searchTable('Laravel')
            ->assertCanSeeTableRecords([$matchingTag])
            ->assertCanNotSeeTableRecords([$nonMatchingTag]);
    });

    it('auto-generates slug from name', function () {
        livewire(CreateTag::class)
            ->fillForm([
                'name' => 'My New Tag',
            ])
            ->assertFormSet([
                'slug' => 'my-new-tag',
            ]);
    });

    it('validates unique slug', function () {
        Tag::factory()->create(['slug' => 'existing-tag']);

        livewire(CreateTag::class)
            ->fillForm([
                'name' => 'Existing Tag',
                'slug' => 'existing-tag',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });

    it('displays posts count', function () {
        $tag = Tag::factory()->create();
        $posts = Post::factory()->count(3)->create();
        $tag->posts()->attach($posts);

        livewire(ListTags::class)
            ->assertTableColumnExists('posts_count');
    });

    it('can access resource via URL', function () {
        $this->get(TagResource::getUrl('index'))
            ->assertSuccessful();

        $this->get(TagResource::getUrl('create'))
            ->assertSuccessful();

        $tag = Tag::factory()->create();
        $this->get(TagResource::getUrl('edit', ['record' => $tag]))
            ->assertSuccessful();
    });
});
