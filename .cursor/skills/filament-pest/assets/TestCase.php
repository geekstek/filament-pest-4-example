<?php

// Template: TestCase for Filament 4 package testing
// Replace: VendorName\PackageName, PackagePlugin, PackageServiceProvider, User model paths
//
// CRITICAL: The DataStore singleton fix is REQUIRED for Filament 4.
// Without it, ALL tests will crash with:
//   ViewErrorBag::put(): Argument #2 ($bag) must be of type MessageBag, null given

namespace VendorName\PackageName\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;
use VendorName\PackageName\PackagePlugin;
use VendorName\PackageName\PackageServiceProvider;
use VendorName\PackageName\Tests\Fixtures\Models\User;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Fix Filament 4 DataStore bind() → singleton
        $this->app->instance(DataStore::class, app()->make(DataStore::class));

        Factory::guessFactoryNamesUsing(function (string $modelName) {
            if ($modelName === User::class) {
                return 'VendorName\\PackageName\\Tests\\Fixtures\\Database\\Factories\\UserFactory';
            }

            return 'VendorName\\PackageName\\Database\\Factories\\' . class_basename($modelName) . 'Factory';
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
            SchemasServiceProvider::class,        // Filament 4 requires this
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            PackageServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        // Only test-only migrations here (e.g. users table)
        // Package migrations auto-load via ServiceProvider::runsMigrations()
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations');
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        $databasePath = __DIR__ . '/../database/testing.sqlite';

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }

        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
        ]);

        config()->set('view.paths', [
            __DIR__ . '/Fixtures/resources/views',
            resource_path('views'),
        ]);

        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        config()->set('auth.providers.users.model', User::class);
    }
}

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugin(PackagePlugin::make());
    }
}
