<?php

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Filament\Resources\MonthlyClosings\MonthlyClosingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyClosing extends EditRecord
{
    protected static string $resource = MonthlyClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
