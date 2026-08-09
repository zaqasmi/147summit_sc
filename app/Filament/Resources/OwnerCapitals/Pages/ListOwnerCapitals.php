<?php

namespace App\Filament\Resources\OwnerCapitals\Pages;

use App\Filament\Resources\OwnerCapitals\OwnerCapitalResource;
use App\Models\OwnerCapital;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListOwnerCapitals extends ListRecords
{
    protected static string $resource = OwnerCapitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordCapitalRecoveryIncome')
                ->label('Record recovery income')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('success')
                ->modalHeading('Record capital recovery income')
                ->modalSubmitActionLabel('Record recovery')
                ->form([
                    DatePicker::make('entry_date')
                        ->label('Date')
                        ->default(today())
                        ->required(),
                    TextInput::make('amount')
                        ->label('Recovery amount')
                        ->prefix('Rs')
                        ->numeric()
                        ->inputMode('decimal')
                        ->minValue(0.01)
                        ->required(),
                    TextInput::make('description')
                        ->default('Capital recovery income'),
                    Textarea::make('notes')
                        ->helperText('Use this when money is set aside or received specifically against your capital investment.'),
                ])
                ->action(function (array $data): void {
                    OwnerCapital::create([
                        'entry_date' => $data['entry_date'],
                        'type' => 'capital_reduction',
                        'amount' => round((float) $data['amount'], 2),
                        'description' => $data['description'] ?: 'Capital recovery income',
                        'notes' => $data['notes'] ?? null,
                    ]);
                })
                ->successNotificationTitle('Capital recovery income recorded'),
            CreateAction::make(),
        ];
    }
}
