<?php

namespace App\Filament\Resources\GameParticipants\Pages;

use App\Filament\Resources\GameParticipants\GameParticipantResource;
use App\Models\SnookerTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGameParticipants extends ListRecords
{
    protected static string $resource = GameParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All'),
        ];

        foreach (SnookerTable::query()->orderBy('number')->get() as $snookerTable) {
            $tabs['table_' . $snookerTable->id] = Tab::make($snookerTable->name)
                ->query(fn (Builder $query): Builder => $query->whereHas(
                    'gameSession',
                    fn (Builder $sessionQuery): Builder => $sessionQuery->where('snooker_table_id', $snookerTable->id),
                ));
        }

        return $tabs;
    }
}
