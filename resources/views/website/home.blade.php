@extends('website.layout')

@php
    use App\Support\PublicStorageUrl;
    use Illuminate\Support\Str;

    $publicStorageUrl = fn (mixed $path): ?string => PublicStorageUrl::make($path);
    $primarySlide = $slides->first();
    $heroImage = $publicStorageUrl($primarySlide?->image_path) ?: asset('website-assets/147-summit-cover.jpeg');
    $heroTitle = $primarySlide?->title ?? ($settings['homepage_title'] ?? '147 Summit Snooker Club Gilgit');
    $heroSubtitle = $primarySlide?->subtitle ?? ($settings['homepage_subtitle'] ?? 'Master the table. Conquer the summit.');
    $displayTournaments = $featuredTournaments->isNotEmpty() ? $featuredTournaments : $upcomingTournaments->take(3);
    $assetGalleryItems = collect([
        ['title' => 'Main Hall', 'album' => 'Club', 'type' => 'image', 'public_url' => asset('website-assets/gallery/club-wide-room.jpeg')],
        ['title' => 'Tournament Night', 'album' => 'Tournament', 'type' => 'image', 'public_url' => asset('website-assets/gallery/tournament-night-2.png')],
        ['title' => 'Match Table', 'album' => 'Club', 'type' => 'image', 'public_url' => asset('website-assets/gallery/table-action-2.png')],
        ['title' => 'Player Break', 'album' => 'Tournament', 'type' => 'image', 'public_url' => asset('website-assets/gallery/table-action-3.png')],
        ['title' => 'Club Room', 'album' => 'Club', 'type' => 'image', 'public_url' => asset('website-assets/gallery/club-room-1.png')],
        ['title' => 'Cue Action', 'album' => 'Tournament', 'type' => 'image', 'public_url' => asset('website-assets/gallery/tournament-action-2.png')],
        ['title' => 'Evening Frames', 'album' => 'Tournament', 'type' => 'image', 'public_url' => asset('website-assets/gallery/tournament-action-1.png')],
        ['title' => 'Table Lineup', 'album' => 'Club', 'type' => 'image', 'public_url' => asset('website-assets/gallery/table-action-1.png')],
    ])->map(fn (array $item): object => (object) $item);
    $visibleGalleryItems = $galleryItems->concat($assetGalleryItems)->take(8);
@endphp

@section('title', $settings['site_name'] ?? '147 Summit Snooker Club')
@section('description', $heroSubtitle)

@section('content')
    <section class="hero {{ $heroImage ? 'has-image' : '' }}" @if ($heroImage) style="background-image: url('{{ $heroImage }}')" @endif>
        <div class="wrap hero-inner">
            <div>
                <div class="eyebrow">Snooker tournaments and club CMS</div>
                <h1>{{ $heroTitle }}</h1>
                <p>{{ $heroSubtitle }}</p>
                <div class="actions">
                    <a class="btn primary" href="{{ route('website.tournaments') }}">View tournaments</a>
                    <a class="btn secondary" href="#contact">Contact club</a>
                </div>
            </div>
        </div>
    </section>

    <section id="about">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>{{ $about['title'] }}</h2>
                    <p>{{ $about['excerpt'] }}</p>
                </div>
                <a class="badge" href="{{ route('website.about') }}">Read more</a>
            </div>

            <div class="live-board">
                <div class="card">
                    <h3>Development Vision</h3>
                    <p class="muted">{{ $about['content'] }}</p>
                </div>

                <div class="grid" style="gap: 10px;">
                    @foreach ($about['highlights'] as $highlight)
                        <div class="card">
                            <span class="badge">{{ $loop->iteration }}</span>
                            <h3 style="margin-top: 10px;">{{ $highlight }}</h3>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section style="background: #fff;" data-live-current-tournament data-live-url="{{ route('website.live.current-tournament') }}">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="badge"><span class="live-dot"></span><span data-live-current-state>Live tournament</span></span>
                    <h2 style="margin-top: 12px;" data-live-current-name>{{ $currentTournament?->name ?? 'No active tournament' }}</h2>
                    <p data-live-current-summary>
                        @if ($currentTournament)
                            {{ \App\Models\Tournament::statusOptions()[$currentTournament->status] ?? ucfirst($currentTournament->status) }}
                            · {{ $currentTournament->starts_at?->format('d M Y') ?? 'Date not set' }}
                        @else
                            Start or publish a tournament from admin to show live updates here.
                        @endif
                    </p>
                </div>
                <a class="badge" href="{{ $currentTournament ? route('website.tournament', $currentTournament) : route('website.tournaments') }}" data-live-current-link>
                    Open tournament
                </a>
            </div>

            <div class="live-board">
                <div class="card">
                    <h3>Current progress</h3>
                    <div class="live-meta">
                        <span data-live-current-players>Players: {{ $currentTournament?->registered_players_count ?? 0 }}</span>
                        <span data-live-current-matches>Matches: {{ $currentTournament ? $currentTournament->matches()->count() : 0 }}</span>
                        <span data-live-current-completed>Completed: 0</span>
                        <span data-live-current-updated>Waiting for updates</span>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Round</th>
                                <th>Players</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody data-live-current-matches-body>
                            <tr>
                                <td colspan="4">Loading live matches...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Featured Tournaments</h2>
                    <p>Registration, draw status, and match progress from the tournament system.</p>
                </div>
                <a class="badge" href="{{ route('website.tournaments') }}">All tournaments</a>
            </div>

            <div class="grid three">
                @forelse ($displayTournaments as $tournament)
                    <article class="card">
                        <span class="badge {{ $tournament->status === 'completed' ? '' : 'red' }}">
                            {{ \App\Models\Tournament::statusOptions()[$tournament->status] ?? ucfirst($tournament->status) }}
                        </span>
                        <h3 style="margin-top: 14px;">
                            <a href="{{ route('website.tournament', $tournament) }}">{{ $tournament->name }}</a>
                        </h3>
                        <div class="meta">
                            {{ $tournament->starts_at?->format('d M Y') ?? 'Date not set' }}
                            · {{ $tournament->players_count ?? $tournament->registered_players_count }} players
                            · {{ $tournament->matches_count ?? 0 }} matches
                        </div>
                        <p class="muted">{{ Str::limit(strip_tags((string) $tournament->prize_notes ?: (string) $tournament->rules), 130) }}</p>
                    </article>
                @empty
                    <div class="card">
                        <h3>No published tournaments yet</h3>
                        <p class="muted">Create and publish a tournament from the admin panel to show it here.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section style="background: #fff;">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Upcoming Schedule</h2>
                    <p>Published upcoming and ongoing tournaments.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Type</th>
                            <th>Starts</th>
                            <th>Registration fee</th>
                            <th>Players</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($upcomingTournaments as $tournament)
                            <tr>
                                <td><a href="{{ route('website.tournament', $tournament) }}">{{ $tournament->name }}</a></td>
                                <td>{{ \App\Models\Tournament::typeOptions()[$tournament->type] ?? ucfirst($tournament->type) }}</td>
                                <td>{{ $tournament->starts_at?->format('d M Y') ?? 'Not set' }}</td>
                                <td>Rs {{ number_format((float) $tournament->registration_fee, 2) }}</td>
                                <td>{{ $tournament->players_count ?? $tournament->registered_players_count }}</td>
                                <td><span class="badge">{{ \App\Models\Tournament::statusOptions()[$tournament->status] ?? ucfirst($tournament->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No upcoming published tournaments.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="news">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>News and Events</h2>
                    <p>Latest announcements from the club and tournament desk.</p>
                </div>
            </div>

            <div class="grid three">
                @forelse ($latestNews as $post)
                    @php($image = $publicStorageUrl($post->cover_image_path))
                    <article class="card">
                        @if ($image)
                            <div class="media"><img src="{{ $image }}" alt="{{ $post->title }}"></div>
                        @endif
                        <h3><a href="{{ route('website.news', $post) }}">{{ $post->title }}</a></h3>
                        <div class="meta">{{ $post->published_at?->format('d M Y') }}</div>
                        <p class="muted">{{ Str::limit(strip_tags((string) $post->excerpt ?: (string) $post->body), 130) }}</p>
                    </article>
                @empty
                    <div class="card">
                        <h3>No published news yet</h3>
                        <p class="muted">Publish news or event posts from the CMS.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section id="gallery" style="background: #fff;">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Gallery</h2>
                    <p>Photos and media from tournaments, players, and club events.</p>
                </div>
            </div>

            <div class="grid four">
                @forelse ($visibleGalleryItems as $item)
                    @php($image = $item->public_url ?? $publicStorageUrl($item->file_path ?? null))
                    <article class="card">
                        @if ($image)
                            <div class="media"><img src="{{ $image }}" alt="{{ $item->title }}"></div>
                        @endif
                        <h3>{{ $item->title }}</h3>
                        <div class="meta">{{ $item->album ?: ucfirst($item->type) }}</div>
                    </article>
                @empty
                    <div class="card">
                        <h3>No gallery items yet</h3>
                        <p class="muted">Upload images or video links from the CMS.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Partners and Management</h2>
                    <p>Associations, sponsors, and the club management team.</p>
                </div>
            </div>

            <div class="grid three">
                @foreach ($sponsors->take(3) as $sponsor)
                    @php($logo = $sponsor->logo_url)
                    <article class="card">
                        @if ($logo)
                            <div class="media"><img src="{{ $logo }}" alt="{{ $sponsor->name }}"></div>
                        @endif
                        <h3>{{ $sponsor->name }}</h3>
                        <div class="meta">{{ \App\Models\Sponsor::categoryOptions()[$sponsor->category] ?? ucfirst($sponsor->category) }}</div>
                    </article>
                @endforeach

                @foreach ($teamMembers->take(3) as $member)
                    @php($photo = $member->photo_url)
                    <article class="card">
                        @if ($photo)
                            <div class="media"><img src="{{ $photo }}" alt="{{ $member->name }}"></div>
                        @endif
                        <h3>{{ $member->name }}</h3>
                        <div class="meta">{{ $member->role_title }}</div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="contact" class="contact-band">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Contact</h2>
                    <p class="muted">Send a message to the club administration.</p>
                </div>
                @if (session('contact_status'))
                    <span class="badge">{{ session('contact_status') }}</span>
                @endif
            </div>

            <form class="contact-form" method="POST" action="{{ route('website.contact') }}">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Name" required>
                <input name="phone" value="{{ old('phone') }}" placeholder="Phone">
                <input name="email" value="{{ old('email') }}" placeholder="Email">
                <input name="subject" value="{{ old('subject') }}" placeholder="Subject">
                <textarea class="span-2" name="message" placeholder="Message" required>{{ old('message') }}</textarea>
                <button class="btn primary" type="submit">Send message</button>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const root = document.querySelector('[data-live-current-tournament]');
            if (!root) return;

            const fields = {
                state: root.querySelector('[data-live-current-state]'),
                name: root.querySelector('[data-live-current-name]'),
                summary: root.querySelector('[data-live-current-summary]'),
                link: root.querySelector('[data-live-current-link]'),
                players: root.querySelector('[data-live-current-players]'),
                matches: root.querySelector('[data-live-current-matches]'),
                completed: root.querySelector('[data-live-current-completed]'),
                updated: root.querySelector('[data-live-current-updated]'),
                body: root.querySelector('[data-live-current-matches-body]'),
            };
            let signature = '';

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char]);

            const importantMatches = (matches) => [
                ...matches.filter((match) => match.status === 'ongoing'),
                ...matches.filter((match) => match.status === 'scheduled'),
                ...matches.filter((match) => match.status === 'completed').reverse(),
                ...matches.filter((match) => match.status === 'walkover').reverse(),
            ].slice(0, 6);

            const render = (payload) => {
                if (!payload.tournament) {
                    fields.state.textContent = 'No live tournament';
                    fields.name.textContent = 'No active tournament';
                    fields.summary.textContent = 'Start or publish a tournament from admin to show live updates here.';
                    fields.link.href = '{{ route('website.tournaments') }}';
                    fields.players.textContent = 'Players: 0';
                    fields.matches.textContent = 'Matches: 0';
                    fields.completed.textContent = 'Completed: 0';
                    fields.updated.textContent = 'Waiting for updates';
                    fields.body.innerHTML = '<tr><td colspan="4">No active tournament is available.</td></tr>';
                    return;
                }

                const tournament = payload.tournament;
                const matches = payload.matches ?? [];
                const nextSignature = `${payload.last_updated_at}|${matches.map((match) => `${match.id}:${match.score}:${match.status}:${match.winner ?? ''}`).join('|')}`;
                const changed = signature && signature !== nextSignature;
                signature = nextSignature;

                fields.state.textContent = tournament.status_label;
                fields.name.textContent = tournament.name;
                fields.summary.textContent = `${tournament.type_label} · ${tournament.match_format_label} · ${tournament.starts_at ?? 'Date not set'}`;
                fields.link.href = tournament.url;
                fields.players.textContent = `Players: ${tournament.players_count}`;
                fields.matches.textContent = `Matches: ${tournament.matches_count}`;
                fields.completed.textContent = `Completed: ${tournament.completed_matches_count}`;
                fields.updated.textContent = payload.last_updated_at ? `Updated: ${new Date(payload.last_updated_at).toLocaleTimeString()}` : 'Waiting for updates';

                const rows = importantMatches(matches).map((match) => `
                    <tr>
                        <td>${escapeHtml(match.round_name)} #${escapeHtml(match.match_number)}</td>
                        <td>${escapeHtml(match.player1)} vs ${escapeHtml(match.player2)}</td>
                        <td><strong>${escapeHtml(match.score)}</strong></td>
                        <td><span class="badge">${escapeHtml(match.status_label)}</span></td>
                    </tr>
                `).join('');

                fields.body.innerHTML = rows || '<tr><td colspan="4">Draw has not been generated yet.</td></tr>';

                if (changed) {
                    root.classList.remove('live-flash');
                    void root.offsetWidth;
                    root.classList.add('live-flash');
                }
            };

            const refresh = async () => {
                try {
                    const response = await fetch(root.dataset.liveUrl, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store',
                    });

                    if (response.ok) {
                        render(await response.json());
                    }
                } catch (error) {
                    fields.updated.textContent = 'Live update paused';
                }
            };

            refresh();
            window.setInterval(refresh, 5000);
        })();
    </script>
@endsection
