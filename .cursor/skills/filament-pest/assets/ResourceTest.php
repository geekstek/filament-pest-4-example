<?php

// Template: Pest 4 test for a Filament 4 Resource
// Replace: namespace imports, Resource/Page classes, Model, field names, factory states
//
// Structure:
//   beforeEach — create & authenticate user
//   describe('Resource')      — CRUD, search, sort, filter, validation, schema, URL
//   describe('Table Actions') — custom record actions, bulk actions
//   describe('Header Actions') — edit page header actions

use VendorName\PackageName\Filament\Resources\ExampleResource;
use VendorName\PackageName\Filament\Resources\ExampleResource\Pages\CreateExample;
use VendorName\PackageName\Filament\Resources\ExampleResource\Pages\EditExample;
use VendorName\PackageName\Filament\Resources\ExampleResource\Pages\ListExamples;
use VendorName\PackageName\Models\Example;
use VendorName\PackageName\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ── CRUD ───────────────────────────────────────────────────

describe('ExampleResource', function () {
    it('can render the index page', function () {
        livewire(ListExamples::class)->assertSuccessful();
    });

    it('can render the create page', function () {
        livewire(CreateExample::class)->assertSuccessful();
    });

    it('can render the edit page', function () {
        $record = Example::factory()->create();
        livewire(EditExample::class, ['record' => $record->getRouteKey()])
            ->assertSuccessful();
    });

    it('can list records', function () {
        $records = Example::factory()->count(3)->create();
        livewire(ListExamples::class)->assertCanSeeTableRecords($records);
    });

    it('can create a record', function () {
        livewire(CreateExample::class)
            ->fillForm([
                'name' => 'Test Name',
                'slug' => 'test-name',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Example::class, ['name' => 'Test Name']);
    });

    it('can update a record', function () {
        $record = Example::factory()->create();

        livewire(EditExample::class, ['record' => $record->getRouteKey()])
            ->fillForm(['name' => 'Updated Name', 'slug' => 'updated-name'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($record->refresh()->name)->toBe('Updated Name');
    });

    it('can delete a record', function () {
        $record = Example::factory()->create();

        livewire(EditExample::class, ['record' => $record->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($record);
    });

    it('can delete records in bulk', function () {
        $records = Example::factory()->count(3)->create();

        livewire(ListExamples::class)
            ->callTableBulkAction('delete', $records);

        foreach ($records as $record) {
            $this->assertModelMissing($record);
        }
    });

    // ── Search / Sort / Filter ─────────────────────────────

    it('can search by name', function () {
        $match = Example::factory()->create(['name' => 'Searchable Item']);
        $noMatch = Example::factory()->create(['name' => 'Other Thing']);

        livewire(ListExamples::class)
            ->searchTable('Searchable')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$noMatch]);
    });

    it('can sort by name', function () {
        $records = Example::factory()->count(3)->create();

        livewire(ListExamples::class)
            ->sortTable('name')
            ->assertCanSeeTableRecords($records->sortBy('name'), inOrder: true);
    });

    it('can filter by active status', function () {
        $active = Example::factory()->create(['is_active' => true]);
        $inactive = Example::factory()->create(['is_active' => false]);

        livewire(ListExamples::class)
            ->filterTable('is_active', true)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    });

    // ── Validation ─────────────────────────────────────────

    it('validates required fields', function () {
        livewire(CreateExample::class)
            ->fillForm(['name' => '', 'slug' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'slug' => 'required']);
    });

    it('validates unique slug', function () {
        Example::factory()->create(['slug' => 'taken']);

        livewire(CreateExample::class)
            ->fillForm(['name' => 'New', 'slug' => 'taken'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    });

    // ── Schema assertions ──────────────────────────────────

    it('has correct form schema', function () {
        livewire(CreateExample::class)
            ->assertFormFieldExists('name')
            ->assertFormFieldExists('slug')
            ->assertFormFieldExists('is_active');
    });

    it('has correct table columns', function () {
        livewire(ListExamples::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('slug')
            ->assertTableColumnExists('is_active');
    });

    it('auto-generates slug from name', function () {
        livewire(CreateExample::class)
            ->fillForm(['name' => 'Hello World'])
            ->assertFormSet(['slug' => 'hello-world']);
    });

    // ── URL routes ─────────────────────────────────────────

    it('can access resource via URL', function () {
        $this->get(ExampleResource::getUrl('index'))->assertSuccessful();
        $this->get(ExampleResource::getUrl('create'))->assertSuccessful();

        $record = Example::factory()->create();
        $this->get(ExampleResource::getUrl('edit', ['record' => $record]))->assertSuccessful();
    });
});

// ── Table Actions ──────────────────────────────────────────

describe('ExampleResource Table Actions', function () {
    it('can run a custom table action', function () {
        $record = Example::factory()->create(['is_active' => false]);

        livewire(ListExamples::class)
            ->callTableAction('activate', $record);

        expect($record->refresh()->is_active)->toBeTrue();
    });

    it('can run a custom bulk action', function () {
        $records = Example::factory()->count(3)->create(['is_active' => false]);

        livewire(ListExamples::class)
            ->callTableBulkAction('activate', $records);

        foreach ($records as $record) {
            expect($record->refresh()->is_active)->toBeTrue();
        }
    });
});

// ── Header Actions (Edit Page) ─────────────────────────────

describe('ExampleResource Header Actions', function () {
    it('can run a header action', function () {
        $record = Example::factory()->create(['is_active' => false]);

        livewire(EditExample::class, ['record' => $record->getRouteKey()])
            ->callAction('example_action');

        expect($record->refresh()->is_active)->toBeTrue();
    });

    it('action is hidden when not applicable', function () {
        $record = Example::factory()->create(['is_active' => true]);

        livewire(EditExample::class, ['record' => $record->getRouteKey()])
            ->assertActionHidden('example_action');
    });
});
