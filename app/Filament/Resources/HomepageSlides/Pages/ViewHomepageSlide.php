<?php

namespace App\Filament\Resources\HomepageSlides\Pages;

use App\Filament\Resources\HomepageSlides\HomepageSlideResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHomepageSlide extends ViewRecord
{
    protected static string $resource = HomepageSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
