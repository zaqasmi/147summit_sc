<?php

namespace App\Filament\Resources\HomepageSlides\Pages;

use App\Filament\Resources\HomepageSlides\HomepageSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomepageSlides extends ListRecords
{
    protected static string $resource = HomepageSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
