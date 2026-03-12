---
name: filament-pest
description: >-
  Scaffold and test Filament 4 Resources, Pages, RelationManagers, and Actions
  in a Laravel 12 package using Pest 4 and Orchestra Testbench 10. Use when
  creating Filament resources, writing Filament tests, setting up package
  testing, or debugging Filament 4 + Pest 4 test failures.
---

# Filament 4 + Pest 4 Package Testing

## Version Matrix

| Laravel | Testbench | Filament | Pest | PHPUnit | PHP |
|---------|-----------|----------|------|---------|-----|
| 12.x    | 10.x     | 4.x     | 4.x | 12.x   | 8.3+|

## Workflow

### Creating a new Filament package with tests

1. Read [assets/composer.json](assets/composer.json) — use as `composer.json` template
2. Read [assets/ServiceProvider.php](assets/ServiceProvider.php) — use as ServiceProvider template
3. Read [assets/Plugin.php](assets/Plugin.php) — use as Filament Plugin template
4. Read [assets/TestCase.php](assets/TestCase.php) — use as `tests/TestCase.php` template
5. Read [assets/Pest.php](assets/Pest.php) — use as `tests/Pest.php`
6. Read [assets/phpunit.xml](assets/phpunit.xml) — use as `phpunit.xml`

### Creating a Filament Resource

1. Read [assets/Resource.php](assets/Resource.php) — Filament 4 Resource template
2. Read [assets/ResourceTest.php](assets/ResourceTest.php) — corresponding Pest test

### Creating a Relation Manager

1. Read [assets/RelationManager.php](assets/RelationManager.php) — Filament 4 RelationManager template
2. Read [assets/RelationManagerTest.php](assets/RelationManagerTest.php) — corresponding Pest test

### Creating a custom Page

1. Read [assets/Page.php](assets/Page.php) — Filament 4 Page template
2. Read [assets/PageTest.php](assets/PageTest.php) — corresponding Pest test

### Creating a custom Action

1. Read [assets/Action.php](assets/Action.php) — Filament 4 Action template

### Creating a List Page with Tabs

1. Read [assets/ListPageWithTabs.php](assets/ListPageWithTabs.php) — List page with tab filters

---

## Filament 3 → 4 Migration Quick Reference

### Imports changed

| What | Filament 3 | Filament 4 |
|------|-----------|-----------|
| Form schema | `Filament\Forms\Form` | `Filament\Schemas\Schema` |
| Section | `Filament\Forms\Components\Section` | `Filament\Schemas\Components\Section` |
| Set callback | `Filament\Forms\Set` | `Filament\Schemas\Components\Utilities\Set` |
| Tab | `Filament\Resources\Components\Tab` | `Filament\Schemas\Components\Tabs\Tab` |
| All Actions | `Filament\Tables\Actions\*` | `Filament\Actions\*` |

### API changed

| What | Filament 3 | Filament 4 |
|------|-----------|-----------|
| Form method | `form(Form $form): Form` | `form(Schema $schema): Schema` |
| Form body | `$form->schema([...])` | `$schema->components([...])` |
| Row actions | `->actions([...])` | `->recordActions([...])` |
| Bulk actions | `->bulkActions([BulkActionGroup::make([...])])` | `->groupedBulkActions([...])` |
| Page `$view` | `protected static string $view` | `protected string $view` |
| Nav icon type | `?string` | `string \| BackedEnum \| null` |
| Nav group type | `?string` | `string \| UnitEnum \| null` |

---

## Critical Pitfalls

### 1. DataStore singleton broken (all tests crash)

**Symptom**: `ViewErrorBag::put(): Argument #2 ($bag) must be of type MessageBag, null given`

Filament 4's `SupportServiceProvider` uses `bind()` to register `DataStoreOverride`, creating a new instance (with empty WeakMap) on every resolve. Livewire's `store()->set()` and `store()->get()` hit different instances.

**Fix** — add this line in `TestCase::setUp()` after `parent::setUp()`:

```php
$this->app->instance(DataStore::class, app()->make(DataStore::class));
```

### 2. Missing SchemasServiceProvider

Filament 4 extracted schemas into `filament/schemas`. Register `SchemasServiceProvider::class` in `getPackageProviders()`.

### 3. Deferred filters break filter tests

Filament 4 defers filters by default. Add `->deferFilters(false)` to every `table()` method, otherwise `filterTable()` assertions silently pass without filtering.

### 4. Migration stubs not loaded

`loadMigrationsFrom()` only recognizes `.php` files. Use `.php` extension (not `.php.stub`) for migrations when using `->runsMigrations()`.

### 5. Pest Livewire helper

Always use `use function Pest\Livewire\livewire;` — never `Livewire::test()` directly in Pest files.

---

## Test Pattern Cheatsheet

```php
// Render pages
livewire(ListPosts::class)->assertSuccessful();
livewire(CreatePost::class)->assertSuccessful();
livewire(EditPost::class, ['record' => $post->getRouteKey()])->assertSuccessful();

// CRUD
livewire(CreatePost::class)->fillForm([...])->call('create')->assertHasNoFormErrors();
livewire(EditPost::class, ['record' => $id])->fillForm([...])->call('save')->assertHasNoFormErrors();
livewire(EditPost::class, ['record' => $id])->callAction('delete');

// Table
livewire(ListPosts::class)->assertCanSeeTableRecords($posts);
livewire(ListPosts::class)->searchTable('keyword')->assertCanSeeTableRecords([$match]);
livewire(ListPosts::class)->sortTable('title')->assertCanSeeTableRecords($sorted, inOrder: true);
livewire(ListPosts::class)->filterTable('is_published', true)->assertCanSeeTableRecords([$pub]);

// Actions
livewire(ListPosts::class)->callTableAction('publish', $post);
livewire(ListPosts::class)->callTableBulkAction('publish', $posts);
livewire(EditPost::class, ['record' => $id])->callAction('publish_post');
livewire(EditPost::class, ['record' => $id])->assertActionHidden('publish_post');

// Relation Manager
livewire(CommentsRM::class, ['ownerRecord' => $post, 'pageClass' => EditPost::class])
    ->callTableAction('create', data: [...])
    ->assertHasNoTableActionErrors();
livewire(TagsRM::class, ['ownerRecord' => $post, 'pageClass' => EditPost::class])
    ->callTableAction('attach', data: ['recordId' => $tag->id]);

// Schema assertions
livewire(CreatePost::class)->assertFormFieldExists('title');
livewire(ListPosts::class)->assertTableColumnExists('title');

// URL routes
$this->get(PostResource::getUrl('index'))->assertSuccessful();
$this->get(PostResource::getUrl('edit', ['record' => $post]))->assertSuccessful();

// Validation
livewire(CreatePost::class)->fillForm(['title' => ''])->call('create')
    ->assertHasFormErrors(['title' => 'required']);
```
