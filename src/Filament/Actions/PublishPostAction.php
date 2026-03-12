<?php

namespace Acme\FilamentPosts\Filament\Actions;

use Acme\FilamentPosts\Models\Post;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class PublishPostAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'publish_post';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Publish');

        $this->icon('heroicon-o-check-circle');

        $this->color('success');

        $this->requiresConfirmation();

        $this->modalHeading('Publish Post');

        $this->modalDescription('Are you sure you want to publish this post? It will be visible to the public.');

        $this->modalSubmitActionLabel('Yes, publish it');

        $this->visible(fn (): bool => $this->getRecord() instanceof Post && ! $this->getRecord()->is_published);

        $this->action(function (): void {
            /** @var Post $record */
            $record = $this->getRecord();

            $record->update([
                'is_published' => true,
                'published_at' => $record->published_at ?? now(),
            ]);

            Notification::make()
                ->title('Post published successfully')
                ->success()
                ->send();
        });
    }
}
