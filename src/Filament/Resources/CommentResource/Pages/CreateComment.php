<?php

namespace Acme\FilamentPosts\Filament\Resources\CommentResource\Pages;

use Acme\FilamentPosts\Filament\Resources\CommentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateComment extends CreateRecord
{
    protected static string $resource = CommentResource::class;
}
