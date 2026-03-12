<?php

// Template: Filament 4 custom Action (for Edit page header)
// Replace: VendorName\PackageName, action name, model, logic
//
// Usage in EditPage:
//   protected function getHeaderActions(): array
//   {
//       return [
//           ExampleAction::make(),
//           Actions\DeleteAction::make(),
//       ];
//   }

namespace VendorName\PackageName\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use VendorName\PackageName\Models\Example;

class ExampleAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'example_action';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Do Something');
        $this->icon('heroicon-o-check-circle');
        $this->color('success');
        $this->requiresConfirmation();
        $this->modalHeading('Confirm Action');
        $this->modalDescription('Are you sure?');
        $this->modalSubmitActionLabel('Yes, do it');

        $this->visible(fn (): bool => $this->getRecord() instanceof Example && ! $this->getRecord()->is_active);

        $this->action(function (): void {
            $this->getRecord()->update(['is_active' => true]);

            Notification::make()
                ->title('Action completed')
                ->success()
                ->send();
        });
    }
}
