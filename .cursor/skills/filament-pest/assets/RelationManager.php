<?php

// Template: Filament 4 RelationManager
// Replace: VendorName\PackageName, relationship name, field definitions
//
// Key points:
// - form() uses Schema, not Form
// - Actions from Filament\Actions\*
// - ->recordActions() not ->actions()
// - ->groupedBulkActions() not ->bulkActions()
// - ->deferFilters(false) if using filters
// - For BelongsToMany: use AttachAction/DetachAction/DetachBulkAction
// - For HasMany: use CreateAction/DeleteAction/DeleteBulkAction

namespace VendorName\PackageName\Filament\Resources\ParentResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make(),
                // For BelongsToMany:
                // AttachAction::make()->preloadRecordSelect(),
            ])
            ->recordActions([
                EditAction::make(),
                // For BelongsToMany: DetachAction::make(),
                DeleteAction::make(),
            ])
            ->groupedBulkActions([
                // For BelongsToMany: DetachBulkAction::make(),
                DeleteBulkAction::make(),
            ]);
    }
}
