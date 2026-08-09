<?php

namespace App\Filament\Resources\NewsPosts\Pages;

use App\Filament\Resources\NewsPosts\NewsPostResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNewsPost extends ViewRecord
{
    protected static string $resource = NewsPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
