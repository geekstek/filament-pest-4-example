<?php

namespace Acme\FilamentPosts\Tests;

use Acme\FilamentPosts\FilamentPostsPlugin;
use Acme\FilamentPosts\FilamentPostsServiceProvider;
use Acme\FilamentPosts\Tests\Fixtures\Models\User;
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

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Filament's SupportServiceProvider uses bind() for DataStoreOverride,
        // which replaces Livewire's singleton instance and causes a new DataStore
        // (with an empty WeakMap) on every resolve. Re-register as a singleton.
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
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            FilamentPostsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
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
            ->plugin(FilamentPostsPlugin::make());
    }
}
