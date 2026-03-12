<?php

// Template: Filament 4 custom Page with form
// Replace: VendorName\PackageName, page name, view path, fields
//
// Key Filament 4 changes:
// - $view is NOT static: `protected string $view` (not `protected static string $view`)
// - $navigationIcon: string | BackedEnum | null
// - $navigationGroup: string | UnitEnum | null
// - form(Schema $schema): Schema
// - $schema->components([...])
// - Section from Filament\Schemas\Components\Section

namespace VendorName\PackageName\Filament\Pages;

use BackedEnum;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use UnitEnum;

class ExamplePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Example Page';

    protected static string | UnitEnum | null $navigationGroup = 'System';

    // NOT static in Filament 4
    protected string $view = 'package-name::pages.example';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'field_name' => config('package-name.field_name', 'default'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Title')
                    ->schema([
                        Forms\Components\TextInput::make('field_name')
                            ->required()
                            ->maxLength(255),
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
            ->title('Saved successfully')
            ->success()
            ->send();
    }
}
