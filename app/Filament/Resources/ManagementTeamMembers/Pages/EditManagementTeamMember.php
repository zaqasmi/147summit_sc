<?php

namespace App\Filament\Resources\ManagementTeamMembers\Pages;

use App\Filament\Resources\ManagementTeamMembers\ManagementTeamMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditManagementTeamMember extends EditRecord
{
    protected static string $resource = ManagementTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
