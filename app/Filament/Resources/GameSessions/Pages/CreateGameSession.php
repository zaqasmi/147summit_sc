<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['started_at'] = now();
        $data['created_by'] = auth()->id();

        return $data;
    }
}
