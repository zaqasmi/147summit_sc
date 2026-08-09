<?php

namespace App\Filament\Resources\ManagementTeamMembers;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\ManagementTeamMembers\Pages\CreateManagementTeamMember;
use App\Filament\Resources\ManagementTeamMembers\Pages\EditManagementTeamMember;
use App\Filament\Resources\ManagementTeamMembers\Pages\ListManagementTeamMembers;
use App\Filament\Resources\ManagementTeamMembers\Pages\ViewManagementTeamMember;
use App\Filament\Resources\ManagementTeamMembers\Schemas\ManagementTeamMemberForm;
use App\Filament\Resources\ManagementTeamMembers\Schemas\ManagementTeamMemberInfolist;
use App\Filament\Resources\ManagementTeamMembers\Tables\ManagementTeamMembersTable;
use App\Models\ManagementTeamMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ManagementTeamMemberResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = ManagementTeamMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Management Team';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return ManagementTeamMemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ManagementTeamMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManagementTeamMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListManagementTeamMembers::route('/'),
            'create' => CreateManagementTeamMember::route('/create'),
            'view' => ViewManagementTeamMember::route('/{record}'),
            'edit' => EditManagementTeamMember::route('/{record}/edit'),
        ];
    }
}
