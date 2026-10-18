<?php

namespace App\Filament\Actions;

use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Models\Tournament;
use App\Services\TournamentDrawService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class GenerateNextTournamentRound
{
    public static function make(bool $fromTournament = false): Action
    {
        return Action::make('generateNextRound')
            ->label('Generate next round draw')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->visible(fn (?Tournament $record = null): bool => (auth()->user()?->isAdmin() ?? false)
                && (! $fromTournament || $record?->type === 'knockout'))
            ->requiresConfirmation()
            ->modalDescription('Complete every match in the current round first. Winners will be drawn again. An odd player receives a bye. Unplayed future bracket slots will be replaced; completed rounds are preserved.')
            ->schema($fromTournament ? [] : [
                Select::make('tournament_id')
                    ->label('Tournament')
                    ->options(fn (): array => Tournament::query()->where('type', 'knockout')->whereHas('matches')->orderBy('name')->pluck('name', 'id')->all())
                    ->default(fn ($livewire) => $livewire->tableFilters['tournament_id']['value'] ?? null)
                    ->required()->searchable(),
            ])
            ->action(function (array $data, $livewire, ?Tournament $record = null) use ($fromTournament): void {
                abort_unless(auth()->user()?->isAdmin(), 403);
                $tournament = $fromTournament ? $record : Tournament::findOrFail($data['tournament_id']);
                try {
                    $round = app(TournamentDrawService::class)->generateNextRound($tournament);
                    Notification::make()->title('Round '.$round.' draw generated')->success()->send();
                    $livewire->redirect(TournamentMatchResource::getUrl('index', [
                        'filters' => ['tournament_id' => ['value' => $tournament->id]],
                    ]));
                } catch (ValidationException $exception) {
                    Notification::make()->title('Next round could not be generated')
                        ->body(collect($exception->errors())->flatten()->first())->danger()->send();
                }
            });
    }
}
