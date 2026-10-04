<?php

namespace App\Filament\Resources\CustomerDues\Tables;

use App\Filament\Support\TableSummaries;
use App\Services\CustomerDuePdfReport;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->summarize(TableSummaries::recordCount())
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('opening_balance')
                    ->label('Opening')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_charged')
                    ->label('Dues added')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('total_discounted')
                    ->label('Discounted')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->summarize(self::moneyTotal())
                    ->sortable(),
                TextColumn::make('balance_due')
                    ->label('Balance due')
                    ->formatStateUsing(fn ($state): string => self::money($state))
                    ->badge()
                    ->color(fn ($state): string => (float) $state > 0 ? 'danger' : 'success')
                    ->summarize(self::moneyTotal())
                    ->sortable(),
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
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportPdf')
                        ->label('Export selected dues PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn (): bool => auth()->user()?->canViewCustomerDues() ?? false)
                        ->action(function (Collection $records): StreamedResponse {
                            abort_unless(auth()->user()?->canViewCustomerDues(), 403);

                            $pdf = app(CustomerDuePdfReport::class)->generate($records);

                            return response()->streamDownload(
                                fn () => print ($pdf),
                                'customer-dues-selected-'.now()->format('Y-m-d').'.pdf',
                                ['Content-Type' => 'application/pdf'],
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    private static function moneyTotal(): Sum
    {
        return Sum::make()
            ->label('Total')
            ->formatStateUsing(fn ($state): string => self::money($state));
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
