# Filament Posts — Laravel Package 測試範例

一個完整可運行的 Laravel 自訂 Package 範例，展示如何在以下技術棧中測試 Filament Resource：

| 技術 | 版本 |
|------|------|
| PHP | 8.3 |
| Laravel | 12.x |
| Filament | 4.x |
| Livewire | 3.7.x |
| Pest | 4.x |
| Orchestra Testbench | 10.x |
| Spatie Laravel Package Tools | 1.x |
| PHPUnit | 12.x |

## 目錄結構

```
├── composer.json
├── phpunit.xml
├── config/
│   └── filament-posts.php
├── database/
│   ├── factories/                  # Package Model Factories
│   │   ├── CategoryFactory.php
│   │   ├── CommentFactory.php
│   │   ├── PostFactory.php
│   │   └── TagFactory.php
│   └── migrations/                 # Package 資料表遷移
│       ├── create_categories_table.php
│       ├── create_tags_table.php
│       ├── create_posts_table.php
│       ├── create_post_tag_table.php
│       └── create_comments_table.php
├── resources/views/pages/
│   ├── dashboard.blade.php
│   └── settings.blade.php
├── src/
│   ├── FilamentPostsPlugin.php     # Filament Plugin 入口
│   ├── FilamentPostsServiceProvider.php
│   ├── Models/
│   │   ├── Category.php
│   │   ├── Comment.php
│   │   ├── Post.php
│   │   └── Tag.php
│   └── Filament/
│       ├── Actions/                # 自訂 Actions
│       ├── Pages/                  # Dashboard, Settings
│       └── Resources/              # CRUD Resources + RelationManagers
├── tests/
│   ├── Pest.php                    # Pest 全域設定
│   ├── TestCase.php                # 測試基礎類別 + TestPanelProvider
│   ├── Fixtures/
│   │   ├── Models/User.php         # 測試專用 User Model
│   │   ├── database/
│   │   │   ├── factories/UserFactory.php
│   │   │   └── migrations/         # 僅放測試專用 Migration（如 users 表）
│   │   └── resources/views/
│   └── Feature/
│       ├── PostResourceTest.php
│       ├── CategoryResourceTest.php
│       ├── TagResourceTest.php
│       ├── CommentResourceTest.php
│       └── PagesTest.php
```

### 目錄結構設計原則

- **`database/migrations/`** — 放 Package 本身的資料表遷移，透過 ServiceProvider 的 `->runsMigrations()` 自動載入
- **`tests/Fixtures/database/migrations/`** — 僅放測試環境專用的遷移（例如 `users` 表），不屬於 Package 本身的職責
- **`database/factories/`** — Package Model 的 Factory
- **`tests/Fixtures/database/factories/`** — 測試專用 Model 的 Factory（例如 `UserFactory`）

## 快速開始

```bash
# 安裝依賴
composer install

# 執行所有測試
./vendor/bin/pest

# 執行特定測試檔案
./vendor/bin/pest tests/Feature/PostResourceTest.php

# 執行特定測試案例
./vendor/bin/pest --filter='it can create a post'
```

---

## 測試規範

### 1. `composer.json` 依賴配置

```json
{
    "require": {
        "php": "^8.3",
        "filament/filament": "^4.0",
        "illuminate/contracts": "^12.0",
        "livewire/livewire": "^3.7",
        "spatie/laravel-package-tools": "^1.15.0"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "pestphp/pest-plugin-livewire": "^4.0"
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

> **注意**：`minimum-stability` 必須設為 `"stable"`，Filament 4 已正式發布，不需要 `"dev"` 或 `"beta"`。

### 2. ServiceProvider 配置

使用 `spatie/laravel-package-tools` 註冊 Migration：

```php
class FilamentPostsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('filament-posts')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_categories_table')
            ->hasMigration('create_tags_table')
            ->hasMigration('create_posts_table')
            ->hasMigration('create_post_tag_table')
            ->hasMigration('create_comments_table')
            ->runsMigrations();
    }
}
```

**重點**：

- Migration 檔案放在 `database/migrations/`，使用 `.php` 副檔名（而非 `.php.stub`）
- 呼叫 `->runsMigrations()` 讓 Migration 在安裝時自動載入，測試環境也會自動執行

### 3. TestCase 基礎類別

```php
abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // 【關鍵】修復 Filament 4 的 DataStore 綁定問題
        $this->app->instance(DataStore::class, app()->make(DataStore::class));

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if ($modelName === User::class) {
                return 'Acme\\FilamentPosts\\Tests\\Fixtures\\Database\\Factories\\UserFactory';
            }
            return 'Acme\\FilamentPosts\\Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,        // ← Filament 4 新增
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentPostsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // 僅載入測試專用的 Migration（如 users 表）
        // Package 本身的 Migration 由 ServiceProvider 的 runsMigrations() 自動載入
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations');
    }

    // ...
}
```

**必須註冊的 Service Providers 清單**（Filament 4）：

| Provider | 說明 |
|----------|------|
| `ActionsServiceProvider` | Filament Actions |
| `BladeCaptureDirectiveServiceProvider` | Blade capture 指令 |
| `BladeHeroiconsServiceProvider` | Heroicon 圖示 |
| `BladeIconsServiceProvider` | 圖示基礎 |
| `FilamentServiceProvider` | Filament 核心 |
| `FormsServiceProvider` | 表單元件 |
| `InfolistsServiceProvider` | Infolist 元件 |
| `LivewireServiceProvider` | Livewire 核心 |
| `NotificationsServiceProvider` | 通知系統 |
| `SchemasServiceProvider` | **Filament 4 新增** — Schema 系統 |
| `SupportServiceProvider` | 支援工具 |
| `TablesServiceProvider` | 資料表 |
| `WidgetsServiceProvider` | Widget 元件 |

### 4. Pest 全域配置（`tests/Pest.php`）

```php
use Acme\FilamentPosts\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');
```

- 使用 `RefreshDatabase` 確保每個測試的資料庫狀態一致
- 搭配檔案型 SQLite 時，`RefreshDatabase` 會使用 Transaction 回滾，速度很快

### 5. TestPanelProvider

測試環境需要一個最小化的 Filament Panel 來提供路由和資源載入：

```php
class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(FilamentPostsPlugin::make());
    }
}
```

---

## Filament 4 API 變更對照（相對於 Filament 3）

### Resource / RelationManager

| 項目 | Filament 3 | Filament 4 |
|------|-----------|-----------|
| Form 方法簽名 | `form(Form $form): Form` | `form(Schema $schema): Schema` |
| Form 定義 | `$form->schema([...])` | `$schema->components([...])` |
| Section 來源 | `Filament\Forms\Components\Section` | `Filament\Schemas\Components\Section` |
| Set callback | `Filament\Forms\Set` | `Filament\Schemas\Components\Utilities\Set` |
| Table record actions | `->actions([...])` | `->recordActions([...])` |
| Table bulk actions | `->bulkActions([BulkActionGroup::make([...])])` | `->groupedBulkActions([...])` |
| Action 匯入來源 | `Filament\Tables\Actions\*` | `Filament\Actions\*` |
| Filter 延遲載入 | 預設啟用 | 需顯式設定 `->deferFilters(false)` |
| Tab 來源 | `Filament\Resources\Components\Tab` | `Filament\Schemas\Components\Tabs\Tab` |

### Page

| 項目 | Filament 3 | Filament 4 |
|------|-----------|-----------|
| `$view` 屬性 | `protected static string $view` | `protected string $view`（非 static） |
| `$navigationIcon` 類型 | `?string` | `string \| BackedEnum \| null` |
| `$navigationGroup` 類型 | `?string` | `string \| UnitEnum \| null` |
| 表單方法簽名 | `form(Form $form): Form` | `form(Schema $schema): Schema` |

### 範例：Resource 定義（Filament 4）

```php
use BackedEnum;
use UnitEnum;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PostResource extends Resource
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | UnitEnum | null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                // 表單欄位...
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([...])
            ->filters([...])
            ->deferFilters(false)      // 測試時必須關閉延遲過濾
            ->recordActions([          // 取代 actions()
                Action::make('publish')->action(fn ($record) => ...),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([     // 取代 bulkActions(BulkActionGroup::make([...]))
                DeleteBulkAction::make(),
            ]);
    }
}
```

---

## 測試涵蓋範圍

本範例包含 **102 個測試，356 個 assertions**，涵蓋以下面向：

### Resource 基本 CRUD

```php
it('can render the index page', function () {
    livewire(ListPosts::class)->assertSuccessful();
});

it('can create a post', function () {
    livewire(CreatePost::class)
        ->fillForm([...])
        ->call('create')
        ->assertHasNoFormErrors();
    $this->assertDatabaseHas(Post::class, [...]);
});

it('can update a post', function () {
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->fillForm([...])
        ->call('save')
        ->assertHasNoFormErrors();
});

it('can delete a post', function () {
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->callAction('delete');
    $this->assertModelMissing($post);
});
```

### Table 搜尋 / 排序 / 篩選

```php
it('can search posts by title', function () {
    livewire(ListPosts::class)
        ->searchTable('keyword')
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$noMatch]);
});

it('can filter posts by published status', function () {
    livewire(ListPosts::class)
        ->filterTable('is_published', true)
        ->assertCanSeeTableRecords([$published])
        ->assertCanNotSeeTableRecords([$draft]);
});
```

### Table Actions / Bulk Actions

```php
it('can publish via table action', function () {
    livewire(ListPosts::class)
        ->callTableAction('publish', $post);
});

it('can bulk publish posts', function () {
    livewire(ListPosts::class)
        ->callTableBulkAction('publish', $posts);
});
```

### Header Actions（Edit Page）

```php
it('can publish via header action', function () {
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->callAction('publish_post');
});

it('publish action is hidden when already published', function () {
    livewire(EditPost::class, ['record' => $post->getRouteKey()])
        ->assertActionHidden('publish_post');
});
```

### Relation Managers

```php
it('can list related comments', function () {
    livewire(CommentsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditPost::class,
    ])->assertCanSeeTableRecords($comments);
});

it('can attach existing tag to post', function () {
    livewire(TagsRelationManager::class, [
        'ownerRecord' => $post,
        'pageClass' => EditPost::class,
    ])
        ->callTableAction('attach', data: ['recordId' => $tag->id])
        ->assertHasNoTableActionErrors();
});
```

### 表單驗證

```php
it('validates required fields', function () {
    livewire(CreatePost::class)
        ->fillForm(['title' => '', 'slug' => ''])
        ->call('create')
        ->assertHasFormErrors(['title' => 'required', 'slug' => 'required']);
});
```

### Schema / Column 存在性

```php
it('has correct form schema', function () {
    livewire(CreatePost::class)
        ->assertFormFieldExists('title')
        ->assertFormFieldExists('slug');
});

it('has correct table columns', function () {
    livewire(ListPosts::class)
        ->assertTableColumnExists('title')
        ->assertTableColumnExists('is_published');
});
```

### URL 路由

```php
it('can access resource via URL', function () {
    $this->get(PostResource::getUrl('index'))->assertSuccessful();
    $this->get(PostResource::getUrl('create'))->assertSuccessful();
    $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertSuccessful();
});
```

### 自訂 Page 測試

```php
it('can render the settings page', function () {
    livewire(Settings::class)->assertSuccessful();
});

it('can update settings', function () {
    livewire(Settings::class)
        ->fillForm([...], 'data')
        ->call('save')
        ->assertHasNoFormErrors();
});
```

---

## 踩坑指南

### 坑 1：Filament 4 DataStore 綁定問題（致命錯誤）

**症狀**：所有測試報錯

```
ViewErrorBag::put(): Argument #2 ($bag) must be of type MessageBag, null given
```

**根本原因**：

Filament 4 的 `SupportServiceProvider` 使用 `bind()` 註冊 `DataStoreOverride`：

```php
// vendor/filament/support/src/SupportServiceProvider.php
$this->app->bind(DataStore::class, DataStoreOverride::class);
```

`bind()` 是工廠綁定——每次 `app(DataStore::class)` 都會建立一個**全新的** `DataStoreOverride` 實例，各自擁有獨立的空 `WeakMap`。Livewire 的 `store($this)->set(...)` 和 `store($this)->get(...)` 在不同實例上操作，導致存入的 `errorBag` 在讀取時永遠是 `null`。

**修復方式**：在 `TestCase::setUp()` 中重新註冊為 singleton：

```php
protected function setUp(): void
{
    parent::setUp();
    $this->app->instance(DataStore::class, app()->make(DataStore::class));
}
```

這行程式碼：
1. `app()->make(DataStore::class)` — 透過 Filament 的 bind 建立一個 `DataStoreOverride` 實例
2. `$this->app->instance(...)` — 將該實例作為 singleton 固定註冊

從此所有 `app(DataStore::class)` 呼叫都返回同一個實例，WeakMap 資料得以保持一致。

---

### 坑 2：必須註冊 `SchemasServiceProvider`

**症狀**：Schema 相關類別找不到、表單無法渲染

**原因**：Filament 4 將原本嵌入 Forms 中的 Schema 系統獨立為 `filament/schemas`

**修復**：在 `getPackageProviders()` 中加入：

```php
use Filament\Schemas\SchemasServiceProvider;

// 在 providers 陣列中加入
SchemasServiceProvider::class,
```

---

### 坑 3：`deferFilters(false)` — 篩選測試不生效

**症狀**：`filterTable()` 測試通過但資料未被篩選，`assertCanNotSeeTableRecords()` 失敗

**原因**：Filament 4 預設啟用延遲篩選（deferred filters），篩選條件不會立即套用

**修復**：在 Table 定義中加入：

```php
public static function table(Table $table): Table
{
    return $table
        ->columns([...])
        ->filters([...])
        ->deferFilters(false);  // 確保篩選立即生效
}
```

---

### 坑 4：Migration 檔案副檔名必須是 `.php`

**症狀**：使用 `->runsMigrations()` 但資料表沒有建立

```
SQLSTATE[HY000]: General error: 1 no such table: posts
```

**原因**：Laravel 的 Migrator 只辨識以 `.php` 結尾的路徑：

```php
// vendor/laravel/framework/src/Illuminate/Database/Migrations/Migrator.php
->flatMap(fn ($path) => str_ends_with($path, '.php')
    ? [$path]
    : $this->files->glob($path.'/*_*.php'))
```

如果檔案是 `.php.stub`，`loadMigrationsFrom()` 會嘗試將其作為目錄進行 glob，找不到任何檔案。

**修復**：Migration 檔案使用 `.php` 副檔名，而非 `.php.stub`。`spatie/laravel-package-tools` 的 `hasMigration()` 會優先尋找 `.php`，也支援 publishing。

---

### 坑 5：Page 的 `$view` 屬性不再是 static

**症狀**：

```
Cannot redeclare property with a different type
```

**原因**：Filament 4 的 `Page` 基礎類別將 `$view` 改為實例屬性

**修復**：

```php
// Filament 3
protected static string $view = 'my-package::pages.dashboard';

// Filament 4
protected string $view = 'my-package::pages.dashboard';
```

---

### 坑 6：`$navigationIcon` 和 `$navigationGroup` 類型變更

**症狀**：

```
Declaration must be compatible with ... string|BackedEnum|null
```

**修復**：

```php
use BackedEnum;
use UnitEnum;

// Filament 3
protected static ?string $navigationIcon = 'heroicon-o-home';
protected static ?string $navigationGroup = 'Content';

// Filament 4
protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';
protected static string | UnitEnum | null $navigationGroup = 'Content';
```

---

### 坑 7：Action 匯入路徑全面變更

**症狀**：`Class 'Filament\Tables\Actions\EditAction' not found`

**原因**：Filament 4 將所有 Action 統一移至 `Filament\Actions` 命名空間

**修復**：

```php
// Filament 3
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;

// Filament 4
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
```

---

### 坑 8：`Form` 類別被 `Schema` 取代

**症狀**：`Class 'Filament\Forms\Form' not found`

**修復**：

```php
// Filament 3
use Filament\Forms\Form;
public static function form(Form $form): Form
{
    return $form->schema([...]);
}

// Filament 4
use Filament\Schemas\Schema;
public static function form(Schema $schema): Schema
{
    return $schema->components([...]);
}
```

`Section` 同樣遷移：

```php
// Filament 3
use Filament\Forms\Components\Section;

// Filament 4
use Filament\Schemas\Components\Section;
```

---

### 坑 9：`Tab` 類別路徑變更

**症狀**：List Page 的 Tab 篩選無法運作

**修復**：

```php
// Filament 3
use Filament\Resources\Components\Tab;

// Filament 4
use Filament\Schemas\Components\Tabs\Tab;
```

---

### 坑 10：測試環境資料庫選擇

| 方式 | 優點 | 缺點 |
|------|------|------|
| SQLite `:memory:` | 最快、無殘留檔案 | 每次測試都重跑 Migration |
| SQLite 檔案 + `RefreshDatabase` | Transaction 回滾快速、可查看資料庫 | 需忽略 `.sqlite` 檔案 |
| MySQL/PostgreSQL | 最接近生產環境 | 設定複雜、速度較慢 |

本範例使用檔案型 SQLite + `RefreshDatabase`：

```php
// TestCase.php
$databasePath = __DIR__ . '/../database/testing.sqlite';
if (! file_exists($databasePath)) {
    touch($databasePath);
}
config()->set('database.connections.testing', [
    'driver' => 'sqlite',
    'database' => $databasePath,
    'prefix' => '',
]);
```

```php
// Pest.php
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
```

記得在 `.gitignore` 中加入：

```
database/testing.sqlite
```

---

## 版本對應關係

| Laravel | Testbench | Filament | Pest | PHPUnit | PHP |
|---------|-----------|----------|------|---------|-----|
| 12.x | 10.x | 4.x | 4.x | 12.x | 8.3+ |
| 11.x | 9.x | 3.x | 3.x | 11.x | 8.2+ |
| 10.x | 8.x | 3.x | 2.x | 10.x | 8.1+ |

## License

MIT
