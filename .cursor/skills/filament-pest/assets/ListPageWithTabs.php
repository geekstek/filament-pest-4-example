<?php

// Template: Filament 4 List Page with Tab filters
// Replace: namespace, Resource class, model, tab conditions
//
// Key Filament 4 change:
//   Tab from Filament\Schemas\Components\Tabs\Tab
//   (NOT Filament\Resources\Components\Tab)

namespace VendorName\PackageName\Filament\Resources\ExampleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use VendorName\PackageName\Filament\Resources\ExampleResource;
use VendorName\PackageName\Models\Example;

class ListExamples extends ListRecords
{
    protected static string $resource = ExampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'inactive' => Tab::make('Inactive')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false))
                ->badge(fn () => Example::where('is_active', false)->count()),
        ];
    }
}
