<?php

namespace App\Filament\Resources\NewsPosts;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\NewsPosts\Pages\CreateNewsPost;
use App\Filament\Resources\NewsPosts\Pages\EditNewsPost;
use App\Filament\Resources\NewsPosts\Pages\ListNewsPosts;
use App\Filament\Resources\NewsPosts\Pages\ViewNewsPost;
use App\Filament\Resources\NewsPosts\Schemas\NewsPostForm;
use App\Filament\Resources\NewsPosts\Schemas\NewsPostInfolist;
use App\Filament\Resources\NewsPosts\Tables\NewsPostsTable;
use App\Models\NewsPost;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsPostResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = NewsPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'News & Events';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return NewsPostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NewsPostInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsPostsTable::configure($table);
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
            'index' => ListNewsPosts::route('/'),
            'create' => CreateNewsPost::route('/create'),
            'view' => ViewNewsPost::route('/{record}'),
            'edit' => EditNewsPost::route('/{record}/edit'),
        ];
    }
}
