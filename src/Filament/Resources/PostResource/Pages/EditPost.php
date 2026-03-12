<?php

namespace Acme\FilamentPosts\Filament\Resources\PostResource\Pages;

use Acme\FilamentPosts\Filament\Actions\ArchivePostAction;
use Acme\FilamentPosts\Filament\Actions\PublishPostAction;
use Acme\FilamentPosts\Filament\Resources\PostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PublishPostAction::make(),
            ArchivePostAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
