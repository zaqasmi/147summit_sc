<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Actions\GenerateNextTournamentRound;
use App\Filament\Resources\TournamentDrawSettings\TournamentDrawSettingResource;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Filament\Support\TableSummaries;
use App\Models\Tournament;
use App\Models\TournamentDrawSetting;
use App\Services\TournamentDrawService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->summarize(TableSummaries::recordCount())
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Tournament::typeOptions()[$state] ?? ucfirst($state))
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('registration_closes_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('registration_fee')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2))
                    ->sortable(),
                TextColumn::make('max_players')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('registered_players_count')
                    ->label('Players')
                    ->numeric(),
                TextColumn::make('registration_fee_collected')
                    ->label('Collected')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextColumn::make('match_format')
                    ->formatStateUsing(fn (string $state): string => Tournament::matchFormatOptions()[$state] ?? $state)
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ongoing' => 'warning',
                        'completed' => 'success',
                        default => 'info',
                    })
                    ->searchable(),
                TextColumn::make('draw_generated_at')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->boolean(),
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
                SelectFilter::make('status')
                    ->options(Tournament::statusOptions()),
                SelectFilter::make('type')
                    ->options(Tournament::typeOptions()),
            ])
            ->recordActions([
                Action::make('drawSettings')
                    ->label('Draw settings')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (Tournament $record): bool => $record->type === 'knockout')
                    ->url(function (Tournament $record): string {
                        $setting = TournamentDrawSetting::query()->where('tournament_id', $record->id)->first();

                        return $setting
                            ? TournamentDrawSettingResource::getUrl('edit', ['record' => $setting])
                            : TournamentDrawSettingResource::getUrl('create', ['tournament_id' => $record->id]);
                    }),
                Action::make('generateDraw')
                    ->label('Generate draw')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Tournament $record): bool => $record->type === 'knockout')
                    ->action(function (Tournament $record, $livewire): void {
                        try {
                            app(TournamentDrawService::class)->generateKnockout($record);

                            Notification::make()
                                ->title('Tournament draw generated')
                                ->success()
                                ->send();

                            $livewire->redirect(TournamentMatchResource::getUrl('index', [
                                'filters' => ['tournament_id' => ['value' => $record->id]],
                            ]));
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Draw could not be generated')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();
                        }
                    }),
                GenerateNextTournamentRound::make(fromTournament: true),
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
