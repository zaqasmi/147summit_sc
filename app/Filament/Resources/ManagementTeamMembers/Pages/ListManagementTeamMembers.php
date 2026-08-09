<?php

namespace App\Filament\Resources\ManagementTeamMembers\Pages;

use App\Filament\Resources\ManagementTeamMembers\ManagementTeamMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManagementTeamMembers extends ListRecords
{
    protected static string $resource = ManagementTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
