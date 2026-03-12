<?php

namespace Acme\FilamentPosts\Filament\Pages;

use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament-posts::pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => config('filament-posts.site_name', 'My Blog'),
            'posts_per_page' => config('filament-posts.posts_per_page', 10),
            'allow_comments' => config('filament-posts.allow_comments', true),
            'moderate_comments' => config('filament-posts.moderate_comments', true),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General Settings')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('posts_per_page')
                            ->label('Posts Per Page')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100)
                            ->default(10),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Comment Settings')
                    ->schema([
                        Forms\Components\Toggle::make('allow_comments')
                            ->label('Allow Comments')
                            ->default(true),

                        Forms\Components\Toggle::make('moderate_comments')
                            ->label('Moderate Comments')
                            ->helperText('When enabled, comments will require approval before being displayed.')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
