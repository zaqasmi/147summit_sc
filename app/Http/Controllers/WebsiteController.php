<?php

namespace App\Http\Controllers;

use App\Models\ClubSetting;
use App\Models\CmsPage;
use App\Models\ContactMessage;
use App\Models\GalleryItem;
use App\Models\HomepageSlide;
use App\Models\ManagementTeamMember;
use App\Models\NewsPost;
use App\Models\Sponsor;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentPlayer;
use App\Models\VisitorVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function home(): View
    {
        $this->recordVisit('website.home');

        if (! $this->tablesReady([
            'club_settings',
            'homepage_slides',
            'tournaments',
            'tournament_players',
            'tournament_matches',
            'news_posts',
            'gallery_items',
            'sponsors',
            'management_team_members',
            'cms_pages',
        ])) {
            return view('website.home', [
                'settings' => [],
                'about' => $this->aboutContent(),
                'currentTournament' => null,
                'slides' => collect(),
                'featuredTournaments' => collect(),
                'upcomingTournaments' => collect(),
                'latestNews' => collect(),
                'galleryItems' => collect(),
                'sponsors' => collect(),
                'teamMembers' => collect(),
                'pages' => collect(),
            ]);
        }

        return view('website.home', [
            'settings' => $this->settings(),
            'about' => $this->aboutContent(),
            'currentTournament' => $this->currentTournament(),
            'slides' => HomepageSlide::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
            'featuredTournaments' => Tournament::query()
                ->where('is_published', true)
                ->where('is_featured', true)
                ->withCount(['players', 'matches'])
                ->orderByDesc('starts_at')
                ->limit(3)
                ->get(),
            'upcomingTournaments' => Tournament::query()
                ->where('is_published', true)
                ->whereIn('status', ['upcoming', 'ongoing'])
                ->withCount('players')
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
            'latestNews' => NewsPost::query()
                ->where('is_published', true)
                ->latest('published_at')
                ->limit(3)
                ->get(),
            'galleryItems' => GalleryItem::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->latest()
                ->limit(8)
                ->get(),
            'sponsors' => Sponsor::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'teamMembers' => ManagementTeamMember::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'pages' => CmsPage::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->limit(6)
                ->get(),
        ]);
    }

    public function tournaments(): View
    {
        $this->recordVisit('website.tournaments');

        return view('website.tournaments', [
            'settings' => $this->settings(),
            'tournaments' => Tournament::query()
                ->where('is_published', true)
                ->withCount(['players', 'matches'])
                ->orderByDesc('starts_at')
                ->paginate(12),
        ]);
    }

    public function about(): View
    {
        $this->recordVisit('website.about');

        return view('website.about', [
            'settings' => $this->settings(),
            'about' => $this->aboutContent(),
        ]);
    }

    public function tournament(Tournament $tournament): View
    {
        abort_unless($tournament->is_published, 404);

        $this->recordVisit('website.tournament');

        return view('website.tournament-show', [
            'settings' => $this->settings(),
            'tournament' => $tournament->load([
                'players' => fn ($query) => $query->orderByRaw('seed is null')->orderBy('seed')->orderBy('full_name'),
                'matches.player1',
                'matches.player2',
                'matches.winner',
            ]),
        ]);
    }

    public function currentTournamentLive(): JsonResponse
    {
        if (! $this->tablesReady(['tournaments', 'tournament_players', 'tournament_matches'])) {
            return $this->liveResponse(['tournament' => null]);
        }

        return $this->liveResponse($this->tournamentPayload($this->currentTournament()));
    }

    public function tournamentLive(Tournament $tournament): JsonResponse
    {
        abort_unless($tournament->is_published, 404);

        return $this->liveResponse($this->tournamentPayload($tournament));
    }

    public function news(NewsPost $newsPost): View
    {
        abort_unless($newsPost->is_published, 404);

        $this->recordVisit('website.news');

        return view('website.news-show', [
            'settings' => $this->settings(),
            'post' => $newsPost,
        ]);
    }

    public function page(CmsPage $cmsPage): View
    {
        abort_unless($cmsPage->is_published, 404);

        $this->recordVisit('website.page');

        return view('website.page-show', [
            'settings' => $this->settings(),
            'page' => $cmsPage,
        ]);
    }

    public function contact(Request $request): RedirectResponse
    {
        ContactMessage::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]));

        return back()->with('contact_status', 'Your message has been received.');
    }

    /**
     * @return array<string, string>
     */
    private function settings(): array
    {
        if (! Schema::hasTable('club_settings')) {
            return [];
        }

        return ClubSetting::query()
            ->orderBy('sort_order')
            ->pluck('value', 'key')
            ->map(fn (?string $value): string => (string) $value)
            ->all();
    }

    private function recordVisit(string $routeName): void
    {
        if (auth()->check() || ! Schema::hasTable('visitor_visits')) {
            return;
        }

        try {
            VisitorVisit::query()->create([
                'route_name' => $routeName,
                'path' => request()->path(),
                'visitor_hash' => $this->visitorHash(),
                'user_agent' => str(request()->userAgent() ?? '')->limit(500, '')->toString(),
                'referrer' => str((string) request()->headers->get('referer'))->limit(500, '')->toString(),
                'visited_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::debug('Unable to record website visit.', [
                'route' => $routeName,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function visitorHash(): ?string
    {
        $ip = request()->ip();

        if (blank($ip)) {
            return null;
        }

        return hash('sha256', $ip.'|'.config('app.key'));
    }

    /**
     * @return array{title: string, excerpt: string, content: string, highlights: array<int, string>}
     */
    private function aboutContent(): array
    {
        $fallback = [
            'title' => 'About 147 Summit Snooker Club',
            'excerpt' => 'A Gilgit-based snooker club working to grow organized cue sports across Gilgit-Baltistan.',
            'content' => '147 Summit Snooker Club, Gilgit is building a structured snooker platform for the region. The club\'s development vision includes the proposed Gilgit-Baltistan Open Snooker Championship 2026, with a focus on identifying promising players, promoting the sport among youth, and connecting local talent with wider national and international snooker pathways. Gilgit-Baltistan has an active snooker community, but players often face distance, travel costs, and limited access to recognized competitive structures. 147 Summit aims to reduce that gap through credible regional tournaments, technical standards, qualified refereeing, coaching development, and disciplined tournament administration.',
            'highlights' => [
                'Grassroots snooker development in Gilgit-Baltistan.',
                'Youth engagement, talent identification, and player progression.',
                'Regional championships aligned with recognized technical standards.',
                'Referee, coaching, and tournament administration capacity building.',
            ],
        ];

        if (! Schema::hasTable('cms_pages')) {
            return $fallback;
        }

        $page = CmsPage::query()
            ->where('is_published', true)
            ->where(fn ($query) => $query
                ->where('slug', 'about-147-summit-snooker-club')
                ->orWhere('section', 'about'))
            ->orderBy('sort_order')
            ->first();

        if (! $page) {
            return $fallback;
        }

        return [
            'title' => $page->title,
            'excerpt' => $page->excerpt ?: $fallback['excerpt'],
            'content' => $page->content ?: $fallback['content'],
            'highlights' => $fallback['highlights'],
        ];
    }

    private function currentTournament(): ?Tournament
    {
        if (! Schema::hasTable('tournaments')) {
            return null;
        }

        return Tournament::query()
            ->where('is_published', true)
            ->where('status', 'ongoing')
            ->orderByDesc('draw_generated_at')
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first()
            ?? Tournament::query()
                ->where('is_published', true)
                ->where('status', 'upcoming')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '>=', today()))
                ->orderByRaw('starts_at is null')
                ->orderBy('starts_at')
                ->orderByDesc('id')
                ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function tournamentPayload(?Tournament $tournament): array
    {
        if (! $tournament?->exists || ! $tournament->is_published) {
            return ['tournament' => null];
        }

        $tournament->loadCount(['players', 'matches']);

        $matches = $tournament->matches()
            ->with(['player1', 'player2', 'winner'])
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        $players = $tournament->players()
            ->orderByRaw('seed is null')
            ->orderBy('seed')
            ->orderBy('full_name')
            ->get();

        $lastUpdated = collect([
            $tournament->updated_at,
            $matches->max('updated_at'),
            $players->max('updated_at'),
        ])->filter()->sortDesc()->first();

        return [
            'server_time' => now()->toIso8601String(),
            'last_updated_at' => $lastUpdated?->toIso8601String(),
            'tournament' => [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'url' => route('website.tournament', $tournament),
                'status' => $tournament->status,
                'status_label' => Tournament::statusOptions()[$tournament->status] ?? ucfirst($tournament->status),
                'type_label' => Tournament::typeOptions()[$tournament->type] ?? ucfirst($tournament->type),
                'match_format_label' => Tournament::matchFormatOptions()[$tournament->match_format] ?? $tournament->match_format,
                'starts_at' => $tournament->starts_at?->format('d M Y'),
                'players_count' => $tournament->players_count,
                'matches_count' => $tournament->matches_count,
                'completed_matches_count' => $matches->where('status', 'completed')->count(),
            ],
            'players' => $players->map(fn (TournamentPlayer $player): array => [
                'id' => $player->id,
                'seed' => $player->seed,
                'name' => $player->full_name,
                'club' => $player->club_name,
                'registration_number' => $player->registration_number,
                'fee_status' => $player->fee_status,
                'fee_status_label' => TournamentPlayer::feeStatusOptions()[$player->fee_status] ?? ucfirst($player->fee_status),
                'matches_played' => $player->matches_played,
                'matches_won' => $player->matches_won,
                'highest_break' => $player->highest_break,
            ])->values(),
            'matches' => $matches->map(fn (TournamentMatch $match): array => [
                'id' => $match->id,
                'round_number' => $match->round_number,
                'round_name' => $match->round_name,
                'match_number' => $match->match_number,
                'table_number' => $match->table_number,
                'player1' => $match->player1?->full_name ?? 'BYE',
                'player2' => $match->player2?->full_name ?? 'BYE',
                'score' => $match->score_label,
                'winner' => $match->winner?->full_name,
                'status' => $match->status,
                'status_label' => TournamentMatch::statusOptions()[$match->status] ?? ucfirst($match->status),
                'player1_highest_break' => $match->player1_highest_break,
                'player2_highest_break' => $match->player2_highest_break,
                'scheduled_at' => $match->scheduled_at?->format('d M Y h:i A'),
                'updated_at' => $match->updated_at?->toIso8601String(),
            ])->values(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function liveResponse(array $payload): JsonResponse
    {
        return response()
            ->json($payload)
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
    }

    /**
     * @param  array<int, string>  $tables
     */
    private function tablesReady(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
