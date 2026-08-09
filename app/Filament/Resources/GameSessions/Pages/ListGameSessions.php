<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Models\SnookerTable;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListGameSessions extends ListRecords
{
    protected static string $resource = GameSessionResource::class;

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
                ->query(fn (Builder $query): Builder => $query->where('snooker_table_id', $snookerTable->id));
        }

        return $tabs;
    }
}
