<?php

// Template: Pest 4 test for a Filament 4 custom Page
// Replace: namespace imports, Page class, form fields, default values, URL path

use VendorName\PackageName\Filament\Pages\ExamplePage;
use VendorName\PackageName\Tests\Fixtures\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('ExamplePage', function () {
    it('can render the page', function () {
        livewire(ExamplePage::class)->assertSuccessful();
    });

    it('has correct form fields', function () {
        livewire(ExamplePage::class)
            ->assertFormFieldExists('field_name');
    });

    it('loads default values on mount', function () {
        livewire(ExamplePage::class)
            ->assertFormSet([
                'field_name' => 'default_value',
            ]);
    });

    it('can submit the form', function () {
        livewire(ExamplePage::class)
            ->fillForm([
                'field_name' => 'new value',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    });

    it('validates required fields', function () {
        livewire(ExamplePage::class)
            ->fillForm(['field_name' => ''])
            ->call('save')
            ->assertHasFormErrors(['field_name' => 'required']);
    });

    it('can access page via URL', function () {
        $this->get('/admin/example-page')
            ->assertSuccessful();
    });
});
