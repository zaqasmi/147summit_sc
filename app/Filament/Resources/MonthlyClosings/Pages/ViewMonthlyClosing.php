<?php

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Filament\Resources\MonthlyClosings\MonthlyClosingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMonthlyClosing extends ViewRecord
{
    protected static string $resource = MonthlyClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
