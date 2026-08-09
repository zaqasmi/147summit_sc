<?php

namespace App\Filament\Resources\Sponsors\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\Sponsor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SponsorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Sponsor::categoryOptions()[$state] ?? ucfirst($state))
                    ->searchable(),
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public'),
                TextColumn::make('website_url')
                    ->label('Website')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(Sponsor::categoryOptions()),
                SelectFilter::make('is_active')
                    ->label('Active')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
