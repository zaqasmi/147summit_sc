<?php

namespace App\Filament\Resources\GameAddOns\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameAddOnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'addOnItem',
                'player',
                'gameSession.participants.player',
            ]))
            ->columns([
                TextColumn::make('gameSession.id')
                    ->label('Session')
                    ->searchable(),
                TextColumn::make('addOnItem.name')
                    ->searchable(),
                TextColumn::make('item_name')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount()),
                TextColumn::make('unit_price')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyAverage())
                    ->sortable(),
                TextColumn::make('quantity')
                    ->numeric()
                    ->summarize(TableSummaries::numberTotal())
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('charged_to')
                    ->label('Charged to')
                    ->badge()
                    ->searchable(),
                TextColumn::make('charged_player_labels')
                    ->label('Paying player(s)')
                    ->wrap(),
                TextColumn::make('charged_player_payment_status')
                    ->label('Player status / balance')
                    ->wrap(),
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
                //
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
