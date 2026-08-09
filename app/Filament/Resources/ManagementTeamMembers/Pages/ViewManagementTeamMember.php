<?php

namespace App\Filament\Resources\ManagementTeamMembers\Pages;

use App\Filament\Resources\ManagementTeamMembers\ManagementTeamMemberResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewManagementTeamMember extends ViewRecord
{
    protected static string $resource = ManagementTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
