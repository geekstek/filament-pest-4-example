<?php

namespace Acme\FilamentPosts\Filament\Actions;

use Acme\FilamentPosts\Models\Post;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ArchivePostAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'archive_post';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Archive');

        $this->icon('heroicon-o-archive-box');

        $this->color('warning');

        $this->requiresConfirmation();

        $this->modalHeading('Archive Post');

        $this->modalDescription('Are you sure you want to archive this post? It will be unpublished.');

        $this->modalSubmitActionLabel('Yes, archive it');

        $this->visible(fn (): bool => $this->getRecord() instanceof Post && $this->getRecord()->is_published);

        $this->action(function (): void {
            /** @var Post $record */
            $record = $this->getRecord();

            $record->update([
                'is_published' => false,
            ]);

            Notification::make()
                ->title('Post archived successfully')
                ->warning()
                ->send();
        });
    }
}
