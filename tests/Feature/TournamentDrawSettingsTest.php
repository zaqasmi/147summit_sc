<?php

namespace Tests\Feature;

use App\Filament\Resources\TournamentDrawSettings\Pages\CreateTournamentDrawSetting;
use App\Filament\Resources\TournamentDrawSettings\Pages\EditTournamentDrawSetting;
use App\Filament\Resources\TournamentDrawSettings\Pages\ListTournamentDrawSettings;
use App\Filament\Resources\TournamentMatches\Pages\ListTournamentMatches;
use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use App\Filament\Resources\Tournaments\Pages\ListTournaments;
use App\Models\Tournament;
use App\Models\TournamentDrawSetting;
use App\Models\TournamentPlayer;
use App\Models\User;
use App\Services\TournamentDrawService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TournamentDrawSettingsTest extends TestCase
{
    use RefreshDatabase;

    public static function brackets(): array
    {
        return [[3, false], [4, true], [5, false], [6, true], [8, true], [16, false], [32, false]];
    }

    #[DataProvider('brackets')]
    public function test_selected_players_never_face_each_other_in_first_round(int $count, bool $seeded): void
    {
        $tournament = $this->tournament($count, $seeded);
        $players = $tournament->players()->orderBy('id')->get();
        $size = 2;
        while ($size < $count) {
            $size *= 2;
        }
        $selected = $players->take($size / 2)->pluck('id')->all();
        TournamentDrawSetting::create(['tournament_id' => $tournament->id, 'separated_player_ids' => $selected]);

        app(TournamentDrawService::class)->generateKnockout($tournament);

        $firstRound = $tournament->matches()->where('round_number', 1)->get();
        $entrantIds = [];
        foreach ($firstRound as $match) {
            $ids = array_values(array_filter([$match->player1_id, $match->player2_id]));
            $this->assertNotEmpty($ids);
            $this->assertLessThanOrEqual(1, count(array_intersect($ids, $selected)));
            array_push($entrantIds, ...$ids);
            if (count($ids) === 1) {
                $this->assertSame($ids[0], $match->winner_id);
                $next = $match->nextMatch;
                $this->assertContains($match->winner_id, [$next->player1_id, $next->player2_id]);
            }
        }
        $this->assertEqualsCanonicalizing($players->pluck('id')->all(), $entrantIds);
        $this->assertSame($size - 1, $tournament->matches()->count());
    }

    public function test_impossible_settings_preserve_existing_draw_and_do_not_redirect(): void
    {
        $this->admin();
        $tournament = $this->tournament(4);
        app(TournamentDrawService::class)->generateKnockout($tournament);
        $before = $tournament->matches()->get()->toArray();
        TournamentDrawSetting::create([
            'tournament_id' => $tournament->id,
            'separated_player_ids' => $tournament->players()->take(3)->pluck('id')->all(),
        ]);

        Livewire::test(ListTournaments::class)
            ->callTableAction('generateDraw', $tournament)
            ->assertNotified('Draw could not be generated')
            ->assertNoRedirect();
        $this->assertSame($before, $tournament->matches()->get()->toArray());
    }

    public function test_generate_draw_redirects_to_matches_filtered_to_that_tournament(): void
    {
        $this->admin();
        $tournament = $this->tournament(4);
        $other = $this->tournament(2);
        app(TournamentDrawService::class)->generateKnockout($other);
        $filters = ['tournament_id' => ['value' => $tournament->id]];

        Livewire::test(ListTournaments::class)
            ->callTableAction('generateDraw', $tournament)
            ->assertNotified('Tournament draw generated')
            ->assertRedirect(TournamentMatchResource::getUrl('index', ['filters' => $filters]));
        Livewire::withQueryParams(['filters' => $filters])->test(ListTournamentMatches::class)
            ->assertCanSeeTableRecords($tournament->matches()->get())
            ->assertCanNotSeeTableRecords($other->matches()->get());
    }

    public function test_settings_can_be_created_edited_and_cleared_in_separate_module(): void
    {
        $this->admin();
        $tournament = $this->tournament(4);
        $ids = $tournament->players()->take(2)->pluck('id')->all();
        Livewire::test(CreateTournamentDrawSetting::class)->fillForm([
            'tournament_id' => $tournament->id, 'separated_player_ids' => $ids,
        ])->call('create')->assertHasNoFormErrors();
        $setting = TournamentDrawSetting::firstOrFail();
        $this->assertSame($ids, $setting->separated_player_ids);
        Livewire::test(ListTournamentDrawSettings::class)->assertCanSeeTableRecords([$setting]);
        Livewire::test(EditTournamentDrawSetting::class, ['record' => $setting->id])
            ->fillForm(['separated_player_ids' => []])->call('save')->assertHasNoFormErrors();
        $this->assertSame([], $setting->refresh()->separated_player_ids);
        Livewire::test(CreateTournamentDrawSetting::class)->fillForm([
            'tournament_id' => $tournament->id, 'separated_player_ids' => $ids,
        ])->call('create')->assertHasFormErrors(['tournament_id' => 'unique']);
    }

    public function test_settings_reject_players_from_another_tournament(): void
    {
        $tournament = $this->tournament(4);
        $other = $this->tournament(2);
        $this->expectException(ValidationException::class);
        TournamentDrawSetting::create([
            'tournament_id' => $tournament->id, 'separated_player_ids' => [$other->players()->first()->id],
        ]);
    }

    public function test_non_admin_cannot_access_draw_settings(): void
    {
        $this->actingAs(User::create(['name' => 'Sales', 'email' => 'sales@example.test', 'role' => 'sale_manager', 'password' => 'password']));
        Livewire::test(ListTournamentDrawSettings::class)->assertForbidden();
        Livewire::test(CreateTournamentDrawSetting::class)->assertForbidden();
    }

    private function admin(): void
    {
        $this->actingAs(User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'admin', 'password' => 'password']));
    }

    private function tournament(int $count, bool $seeded = false): Tournament
    {
        $tournament = Tournament::create(['name' => 'Draw cup', 'type' => 'knockout', 'match_format' => 'best_of_3']);
        for ($index = 1; $index <= $count; $index++) {
            TournamentPlayer::create([
                'tournament_id' => $tournament->id, 'full_name' => 'Player '.$index,
                'seed' => $seeded ? $index : null,
            ]);
        }

        return $tournament;
    }
}
