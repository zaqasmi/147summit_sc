<?php

namespace App\Filament\Resources\CapitalLiabilities\Tables;

use App\Filament\Support\TableSummaries;
use App\Models\CapitalLiabilityPayment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CapitalLiabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Loan / item')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('lender_name')
                    ->label('Bank / friend')
                    ->searchable(),
                TextColumn::make('source_label')
                    ->label('Source')
                    ->badge(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable(),
                TextColumn::make('principal_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal())
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::paidTotal()),
                TextColumn::make('balance_amount')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(self::balanceTotal())
                    ->color(fn ($state): string => ((float) $state > 0) ? 'danger' : 'success'),
                TextColumn::make('installment_amount')
                    ->label('Installment')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->summarize(TableSummaries::moneyTotal()),
                TextColumn::make('status_label')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record): string => match ($record->status) {
                        'paid' => 'success',
                        'paused' => 'warning',
                        'cancelled' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('due_date')
                    ->date()
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

    private static function paidTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(fn ($query): float => (float) CapitalLiabilityPayment::query()
                ->whereIn('capital_liability_id', self::liabilityIds($query))
                ->sum('amount'))
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }

    private static function balanceTotal(): Summarizer
    {
        return Summarizer::make()
            ->label('Total')
            ->using(function ($query): float {
                $principalById = (clone $query)->pluck('principal_amount', 'id');
                $liabilityIds = $principalById->keys()->all();

                if ($principalById->isEmpty()) {
                    return 0.0;
                }

                $paidById = CapitalLiabilityPayment::query()
                    ->whereIn('capital_liability_id', $liabilityIds)
                    ->selectRaw('capital_liability_id, sum(amount) as paid_total')
                    ->groupBy('capital_liability_id')
                    ->pluck('paid_total', 'capital_liability_id');

                return (float) $principalById->sum(
                    fn ($principal, $liabilityId): float => max(0, (float) $principal - (float) ($paidById[$liabilityId] ?? 0)),
                );
            })
            ->formatStateUsing(fn ($state): string => TableSummaries::money($state));
    }

    /**
     * @return array<int, int>
     */
    private static function liabilityIds($query): array
    {
        return (clone $query)->pluck('id')->all();
    }
}
