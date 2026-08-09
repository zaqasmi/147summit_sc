<?php

namespace App\Filament\Resources\HomepageSlides\Pages;

use App\Filament\Resources\HomepageSlides\HomepageSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditHomepageSlide extends EditRecord
{
    protected static string $resource = HomepageSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
