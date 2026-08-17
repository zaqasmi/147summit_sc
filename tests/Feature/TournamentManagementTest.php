<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentMatchFrame;
use App\Models\TournamentPlayer;
use App\Models\VisitorVisit;
use App\Services\TournamentDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournament_registration_autofills_existing_player_details(): void
    {
        $tournament = Tournament::create([
            'name' => 'August Open',
            'registration_fee' => 500,
            'match_format' => 'best_of_5',
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        $player = Player::create(['name' => 'Shayan Ali']);

        $registration = TournamentPlayer::create([
            'tournament_id' => $tournament->id,
            'player_id' => $player->id,
            'fee_status' => 'unpaid',
        ]);

        $this->assertSame('Shayan Ali', $registration->full_name);
        $this->assertSame('500.00', $registration->registration_fee);
        $this->assertSame('T'.$tournament->id.'-0001', $registration->registration_number);
        $this->assertSame('august-open', $tournament->slug);
    }

    public function test_knockout_draw_creates_bye_and_advances_player(): void
    {
        $tournament = Tournament::create([
            'name' => 'Three Player Cup',
            'type' => 'knockout',
            'match_format' => 'best_of_5',
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        collect(['A Player', 'B Player', 'C Player'])->each(
            fn (string $name): TournamentPlayer => TournamentPlayer::create([
                'tournament_id' => $tournament->id,
                'full_name' => $name,
                'fee_status' => 'paid',
            ]),
        );

        app(TournamentDrawService::class)->generateKnockout($tournament);

        $this->assertSame(3, $tournament->matches()->count());
        $this->assertNotNull($tournament->refresh()->draw_generated_at);
        $this->assertSame('ongoing', $tournament->status);

        $byeMatch = $tournament->matches()->where('notes', 'BYE')->first();
        $this->assertNotNull($byeMatch);
        $this->assertSame('completed', $byeMatch->status);
        $this->assertNotNull($byeMatch->winner_id);

        $final = $tournament->matches()->where('round_name', 'Final')->first();
        $this->assertContains($byeMatch->winner_id, [$final->player1_id, $final->player2_id]);
    }

    public function test_frame_scores_complete_match_and_advance_winner(): void
    {
        $tournament = Tournament::create([
            'name' => 'Four Player Cup',
            'type' => 'knockout',
            'match_format' => 'best_of_5',
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        collect(['A Player', 'B Player', 'C Player', 'D Player'])->each(
            fn (string $name): TournamentPlayer => TournamentPlayer::create([
                'tournament_id' => $tournament->id,
                'full_name' => $name,
                'fee_status' => 'paid',
            ]),
        );

        app(TournamentDrawService::class)->generateKnockout($tournament);

        $match = $tournament->matches()
            ->where('round_number', 1)
            ->whereNotNull('player1_id')
            ->whereNotNull('player2_id')
            ->firstOrFail();

        foreach ([1, 2, 3] as $frameNumber) {
            TournamentMatchFrame::create([
                'tournament_match_id' => $match->id,
                'frame_number' => $frameNumber,
                'player1_score' => 70,
                'player2_score' => 35,
                'winner_id' => $match->player1_id,
                'player1_highest_break' => 42 + $frameNumber,
            ]);
        }

        $match->refresh();
        $nextMatch = $match->nextMatch()->first();
        $slot = $match->next_match_slot === 'player2' ? 'player2_id' : 'player1_id';

        $this->assertSame('completed', $match->status);
        $this->assertSame($match->player1_id, $match->winner_id);
        $this->assertSame(3, $match->player1_frames);
        $this->assertSame(45, $match->player1_highest_break);
        $this->assertSame($match->winner_id, $nextMatch->{$slot});

        $this->getJson(route('website.tournament.live', $tournament))
            ->assertOk()
            ->assertJsonPath('tournament.name', 'Four Player Cup')
            ->assertJsonFragment([
                'score' => '3 - 0',
                'winner' => $match->player1->full_name,
                'status_label' => 'Completed',
            ]);
    }

    public function test_public_website_renders_published_content_and_stores_contact_messages(): void
    {
        $tournament = Tournament::create([
            'name' => 'Public Open',
            'type' => 'knockout',
            'match_format' => 'best_of_5',
            'starts_at' => '2026-08-20',
            'status' => 'upcoming',
            'is_featured' => true,
            'is_published' => true,
        ]);

        NewsPost::create([
            'title' => 'Tournament announced',
            'body' => 'Registrations are open.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('website.home'))
            ->assertOk()
            ->assertSee('Public Open')
            ->assertSee('Tournament announced')
            ->assertSee('Gilgit-Baltistan Open Snooker Championship 2026')
            ->assertSee('Main Hall');

        $this->get(route('website.about'))
            ->assertOk()
            ->assertSee('About 147 Summit Snooker Club')
            ->assertSee('recognized competitive structures');

        $this->get(route('website.tournament', $tournament))
            ->assertOk()
            ->assertSee('Draw & Results', false)
            ->assertSee('Registered Players');

        $this->getJson(route('website.live.current-tournament'))
            ->assertOk()
            ->assertJsonPath('tournament.name', 'Public Open')
            ->assertJsonPath('tournament.status_label', 'Upcoming');

        $this->post(route('website.contact'), [
            'name' => 'Visitor',
            'phone' => '03000000000',
            'message' => 'Please share registration details.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Visitor',
            'status' => 'new',
        ]);
        $this->assertSame(3, VisitorVisit::query()->count());
        $this->assertSame(1, VisitorVisit::query()->distinct('visitor_hash')->count('visitor_hash'));
        $this->assertDatabaseHas('visitor_visits', [
            'route_name' => 'website.home',
            'path' => '/',
        ]);
        $this->assertDatabaseHas('visitor_visits', [
            'route_name' => 'website.about',
            'path' => 'about',
        ]);
        $this->assertDatabaseHas('visitor_visits', [
            'route_name' => 'website.tournament',
            'path' => 'tournaments/public-open',
        ]);
    }
}
