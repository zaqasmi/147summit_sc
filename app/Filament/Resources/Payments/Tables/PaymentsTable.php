<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Filament\Support\TableSummaries;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('gameSession.id')
                    ->label('Session')
                    ->searchable(),
                TextColumn::make('participant.player_label')
                    ->label('Player')
                    ->placeholder('Guest player'),
                TextColumn::make('player.name')
                    ->label('Saved player')
                    ->searchable(),
                TextColumn::make('collectedBy.name')
                    ->label('Collected by')
                    ->searchable(),
                TextColumn::make('payment_date')
                    ->date()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->searchable(),
                TextColumn::make('amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
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
