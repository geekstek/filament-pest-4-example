<?php

// Template: Filament 4 Resource
// Replace: VendorName\PackageName, ExampleResource, Example (model), field definitions
//
// Key Filament 4 changes vs v3:
// - form(Schema $schema): Schema  (not Form $form)
// - $schema->components([...])    (not $form->schema([...]))
// - Section from Filament\Schemas\Components\Section
// - Set from Filament\Schemas\Components\Utilities\Set
// - All Actions from Filament\Actions\*  (not Filament\Tables\Actions\*)
// - ->recordActions([...])        (not ->actions([...]))
// - ->groupedBulkActions([...])   (not ->bulkActions([BulkActionGroup::make([...])]))
// - ->deferFilters(false)         (REQUIRED for filter tests to work)
// - $navigationIcon: string | BackedEnum | null
// - $navigationGroup: string | UnitEnum | null

namespace VendorName\PackageName\Filament\Resources;

use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use UnitEnum;
use VendorName\PackageName\Models\Example;

class ExampleResource extends Resource
{
    protected static ?string $model = Example::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\RichEditor::make('content')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->deferFilters(false)
            ->recordActions([
                // Custom table action example:
                // Action::make('activate')
                //     ->icon('heroicon-o-check-circle')
                //     ->visible(fn ($record) => ! $record->is_active)
                //     ->action(fn ($record) => $record->update(['is_active' => true])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ExampleResource\Pages\ListExamples::route('/'),
            'create' => ExampleResource\Pages\CreateExample::route('/create'),
            'edit' => ExampleResource\Pages\EditExample::route('/{record}/edit'),
        ];
    }
}
