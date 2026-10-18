<?php

namespace Tests\Feature;

use App\Filament\Resources\TournamentMatches\Pages\ListTournamentMatches;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Filament\Resources\Tournaments\Pages\ListTournaments;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchFrame;
use App\Models\TournamentPlayer;
use App\Models\User;
use App\Services\TournamentDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class TournamentNextRoundDrawTest extends TestCase
{
    use RefreshDatabase;

    public function test_rounds_can_be_drawn_repeatedly_through_semi_final_and_final(): void
    {
        $tournament = $this->tournament(64);
        $service = app(TournamentDrawService::class);
        $service->generateKnockout($tournament);
        for ($round = 1; $round <= 5; $round++) {
            $matches = $tournament->matches()->where('round_number', $round)->get();
            foreach ($matches as $match) {
                $match->update(['winner_id' => $match->player1_id]);
            }
            $completed = $tournament->matches()->where('round_number', '<=', $round)->get()->keyBy('id');
            $winners = $matches->pluck('player1_id')->all();
            $this->assertSame($round + 1, $service->generateNextRound($tournament));
            $next = $tournament->matches()->where('round_number', $round + 1)->get();
            $this->assertEqualsCanonicalizing($winners, $next->flatMap(fn ($match) => array_filter([$match->player1_id, $match->player2_id]))->all());
            $this->assertSame($round + 1, $tournament->refresh()->last_drawn_round);
            foreach ($completed as $id => $before) {
                $after = TournamentMatch::findOrFail($id);
                $this->assertSame($before->winner_id, $after->winner_id);
                $this->assertSame('completed', $after->status);
                if ($before->round_number === $round) {
                    $this->assertContains($before->winner_id, [$after->nextMatch->player1_id, $after->nextMatch->player2_id]);
                }
            }
        }
        $this->assertSame('Semi Final', $tournament->matches()->where('round_number', 5)->first()->round_name);
        $final = $tournament->matches()->where('round_number', 6)->first();
        $this->assertSame('Final', $final->round_name);
        $final->update(['winner_id' => $final->player1_id]);
        $this->expectException(ValidationException::class);
        $service->generateNextRound($tournament);
    }

    public function test_odd_winners_receive_one_bye_and_walkovers_qualify(): void
    {
        $tournament = $this->tournament(6);
        $players = $tournament->players()->get();
        foreach ($players->chunk(2)->values() as $index => $pair) {
            $ids = $pair->pluck('id')->values();
            TournamentMatch::create([
                'tournament_id' => $tournament->id, 'round_number' => 1, 'round_name' => 'Round 1',
                'match_number' => $index + 1, 'player1_id' => $ids[0], 'player2_id' => $ids[1],
                'winner_id' => $ids[0], 'status' => $index === 0 ? 'walkover' : 'completed',
                'match_format' => 'best_of_3',
            ]);
        }
        $service = app(TournamentDrawService::class);
        $this->assertSame(2, $service->generateNextRound($tournament));
        $round = $tournament->matches()->where('round_number', 2)->get();
        $this->assertCount(2, $round);
        $bye = $round->firstWhere('notes', 'BYE');
        $this->assertNotNull($bye);
        $this->assertSame($bye->player1_id, $bye->winner_id);
        $this->assertTrue($bye->isComplete());
        $match = $round->firstWhere('notes', null);
        $match->update(['winner_id' => $match->player2_id]);
        $this->assertSame(3, $service->generateNextRound($tournament));
        $final = $tournament->matches()->where('round_number', 3)->first();
        $this->assertEqualsCanonicalizing([$bye->winner_id, $match->winner_id], [$final->player1_id, $final->player2_id]);
        $this->assertSame('Final', $final->round_name);
    }

    public function test_incomplete_round_and_repeated_draw_attempt_preserve_matches(): void
    {
        $tournament = $this->tournament(8);
        $service = app(TournamentDrawService::class);
        $service->generateKnockout($tournament);
        $before = $tournament->matches()->get()->toArray();
        $this->assertDrawBlocked($tournament);
        $this->assertSame($before, $tournament->matches()->get()->toArray());
        foreach ($tournament->matches()->where('round_number', 1)->get() as $match) {
            $match->update(['winner_id' => $match->player1_id]);
        }
        $service->generateNextRound($tournament);
        $before = $tournament->matches()->get()->toArray();
        $this->assertDrawBlocked($tournament);
        $this->assertSame($before, $tournament->matches()->get()->toArray());
    }

    public function test_started_later_match_and_frame_records_are_preserved(): void
    {
        $tournament = $this->tournament(8);
        app(TournamentDrawService::class)->generateKnockout($tournament);
        foreach ($tournament->matches()->where('round_number', 1)->get() as $match) {
            $match->update(['winner_id' => $match->player1_id]);
        }
        $secondRound = $tournament->matches()->where('round_number', 2)->first();
        $frame = TournamentMatchFrame::create([
            'tournament_match_id' => $secondRound->id, 'frame_number' => 1,
            'player1_score' => 50, 'player2_score' => 20, 'winner_id' => $secondRound->player1_id,
        ]);
        $before = $tournament->matches()->get()->toArray();
        $this->assertDrawBlocked($tournament);
        $this->assertSame($before, $tournament->matches()->get()->toArray());
        $this->assertTrue(TournamentMatchFrame::whereKey($frame->id)->exists());
    }

    public function test_tournaments_and_matches_pages_offer_next_round_draw_and_redirect(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'admin', 'password' => 'password']));
        $tournament = $this->tournament(8);
        app(TournamentDrawService::class)->generateKnockout($tournament);
        foreach ($tournament->matches()->where('round_number', 1)->get() as $match) {
            $match->update(['winner_id' => $match->player1_id]);
        }
        $url = TournamentMatchResource::getUrl('index', ['filters' => ['tournament_id' => ['value' => $tournament->id]]]);
        Livewire::test(ListTournaments::class)
            ->callTableAction('generateNextRound', $tournament)
            ->assertNotified('Round 2 draw generated')->assertRedirect($url);
        foreach ($tournament->matches()->where('round_number', 2)->get() as $match) {
            $match->update(['winner_id' => $match->player1_id]);
        }
        Livewire::test(ListTournamentMatches::class)
            ->callAction('generateNextRound', data: ['tournament_id' => $tournament->id])
            ->assertHasNoActionErrors()->assertNotified('Round 3 draw generated')->assertRedirect($url);
    }

    private function assertDrawBlocked(Tournament $tournament): void
    {
        try {
            app(TournamentDrawService::class)->generateNextRound($tournament);
            $this->fail('An unfinished or started round must not be overwritten.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('matches', $exception->errors());
        }
    }

    private function tournament(int $count): Tournament
    {
        $tournament = Tournament::create(['name' => 'Next round cup', 'type' => 'knockout', 'match_format' => 'best_of_3']);
        for ($index = 1; $index <= $count; $index++) {
            TournamentPlayer::create(['tournament_id' => $tournament->id, 'full_name' => 'Player '.$index]);
        }

        return $tournament;
    }
}
