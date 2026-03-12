<?php

// Template: Pest 4 test for a Filament 4 RelationManager
// Replace: namespace imports, RelationManager class, parent model, child model
//
// Two flavors:
//   HasMany  — create/edit/delete on child records
//   BelongsToMany — attach/detach on pivot records

use VendorName\PackageName\Filament\Resources\ParentResource\Pages\EditParent;
use VendorName\PackageName\Filament\Resources\ParentResource\RelationManagers\ItemsRelationManager;
use VendorName\PackageName\Models\Item;
use VendorName\PackageName\Models\Parent;
use VendorName\PackageName\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ── HasMany RelationManager ────────────────────────────────

describe('ParentResource ItemsRelationManager', function () {
    it('can render the relation manager', function () {
        $parent = Parent::factory()->create();

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])->assertSuccessful();
    });

    it('can list related records', function () {
        $parent = Parent::factory()->create();
        $items = Item::factory()->count(3)->create(['parent_id' => $parent->id]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])->assertCanSeeTableRecords($items);
    });

    it('can create a record from relation manager', function () {
        $parent = Parent::factory()->create();

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])
            ->callTableAction('create', data: [
                'name' => 'New Item',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas(Item::class, [
            'name' => 'New Item',
            'parent_id' => $parent->id,
        ]);
    });

    it('can delete a record from relation manager', function () {
        $parent = Parent::factory()->create();
        $item = Item::factory()->create(['parent_id' => $parent->id]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])->callTableAction('delete', $item);

        $this->assertModelMissing($item);
    });

    it('can run a custom action on related record', function () {
        $parent = Parent::factory()->create();
        $item = Item::factory()->create(['parent_id' => $parent->id, 'is_approved' => false]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])->callTableAction('approve', $item);

        expect($item->refresh()->is_approved)->toBeTrue();
    });

    it('can run a custom bulk action', function () {
        $parent = Parent::factory()->create();
        $items = Item::factory()->count(3)->create(['parent_id' => $parent->id, 'is_approved' => false]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $parent,
            'pageClass' => EditParent::class,
        ])->callTableBulkAction('approve', $items);

        foreach ($items as $item) {
            expect($item->refresh()->is_approved)->toBeTrue();
        }
    });
});

// ── BelongsToMany RelationManager ──────────────────────────

// Uncomment and adapt if using BelongsToMany (attach/detach pattern):

// describe('ParentResource TagsRelationManager', function () {
//     it('can attach an existing record', function () {
//         $parent = Parent::factory()->create();
//         $tag = Tag::factory()->create();
//
//         livewire(TagsRelationManager::class, [
//             'ownerRecord' => $parent,
//             'pageClass' => EditParent::class,
//         ])
//             ->callTableAction('attach', data: ['recordId' => $tag->id])
//             ->assertHasNoTableActionErrors();
//
//         expect($parent->refresh()->tags)->toHaveCount(1);
//     });
//
//     it('can detach a record', function () {
//         $parent = Parent::factory()->create();
//         $tag = Tag::factory()->create();
//         $parent->tags()->attach($tag);
//
//         livewire(TagsRelationManager::class, [
//             'ownerRecord' => $parent,
//             'pageClass' => EditParent::class,
//         ])->callTableAction('detach', $tag);
//
//         expect($parent->refresh()->tags)->toHaveCount(0);
//     });
//
//     it('can bulk detach records', function () {
//         $parent = Parent::factory()->create();
//         $tags = Tag::factory()->count(3)->create();
//         $parent->tags()->attach($tags);
//
//         livewire(TagsRelationManager::class, [
//             'ownerRecord' => $parent,
//             'pageClass' => EditParent::class,
//         ])->callTableBulkAction('detach', $tags);
//
//         expect($parent->refresh()->tags)->toHaveCount(0);
//     });
// });
