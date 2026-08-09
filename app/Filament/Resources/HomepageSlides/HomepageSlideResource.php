<?php

namespace App\Filament\Resources\HomepageSlides;

use App\Filament\Concerns\AdminOnlyAccess;
use App\Filament\Resources\HomepageSlides\Pages\CreateHomepageSlide;
use App\Filament\Resources\HomepageSlides\Pages\EditHomepageSlide;
use App\Filament\Resources\HomepageSlides\Pages\ListHomepageSlides;
use App\Filament\Resources\HomepageSlides\Pages\ViewHomepageSlide;
use App\Filament\Resources\HomepageSlides\Schemas\HomepageSlideForm;
use App\Filament\Resources\HomepageSlides\Schemas\HomepageSlideInfolist;
use App\Filament\Resources\HomepageSlides\Tables\HomepageSlidesTable;
use App\Models\HomepageSlide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HomepageSlideResource extends Resource
{
    use AdminOnlyAccess;

    protected static ?string $model = HomepageSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'Homepage Slider';

    protected static string|\UnitEnum|null $navigationGroup = 'Website CMS';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return HomepageSlideForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HomepageSlideInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HomepageSlidesTable::configure($table);
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
            'index' => ListHomepageSlides::route('/'),
            'create' => CreateHomepageSlide::route('/create'),
            'view' => ViewHomepageSlide::route('/{record}'),
            'edit' => EditHomepageSlide::route('/{record}/edit'),
        ];
    }
}
