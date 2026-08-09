@extends('website.layout')

@section('title', $tournament->name.' | '.($settings['site_name'] ?? '147 Summit Snooker Club'))
@section('description', $tournament->prize_notes ?: $tournament->rules ?: $tournament->name)

@section('content')
    <section data-live-tournament data-live-url="{{ route('website.tournament.live', $tournament) }}">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="badge"><span class="live-dot"></span><span data-live-status>{{ \App\Models\Tournament::statusOptions()[$tournament->status] ?? ucfirst($tournament->status) }}</span></span>
                    <h2 style="margin-top: 12px;" data-live-title>{{ $tournament->name }}</h2>
                    <p data-live-summary>
                        {{ \App\Models\Tournament::typeOptions()[$tournament->type] ?? ucfirst($tournament->type) }}
                        · {{ \App\Models\Tournament::matchFormatOptions()[$tournament->match_format] ?? $tournament->match_format }}
                        · {{ $tournament->starts_at?->format('d M Y') ?? 'Date not set' }}
                    </p>
                </div>
                <div class="card" style="min-width: 240px;">
                    <div class="meta">Registration collected</div>
                    <h3>Rs {{ number_format((float) $tournament->registration_fee_collected, 2) }}</h3>
                    <div class="meta"><span data-live-player-count>{{ $tournament->registered_players_count }}</span> registered · {{ $tournament->pending_payments_count }} pending</div>
                    <div class="meta" data-live-updated>Live updates enabled</div>
                </div>
            </div>

            @if ($tournament->prize_notes || $tournament->rules)
                <div class="card">
                    @if ($tournament->prize_notes)
                        <h3>Prize notes</h3>
                        <p class="muted">{{ $tournament->prize_notes }}</p>
                    @endif
                    @if ($tournament->rules)
                        <h3>Rules</h3>
                        <p class="muted">{{ $tournament->rules }}</p>
                    @endif
                </div>
            @endif
        </div>
    </section>

    <section style="background: #fff;">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Draw & Results</h2>
                    <p>Round-wise draw, score, winner, and match status.</p>
                </div>
            </div>

            <div class="draw-grid" data-live-draw-body>
                @forelse ($tournament->matches->sortBy(['round_number', 'match_number'])->groupBy('round_number') as $roundMatches)
                    <div class="draw-round">
                        <h3>{{ $roundMatches->first()->round_name }}</h3>
                        @foreach ($roundMatches as $match)
                            <article class="match-card {{ $match->status }}">
                                <div class="match-head">
                                    <span>Match #{{ $match->match_number }}</span>
                                    <span>{{ \App\Models\TournamentMatch::statusOptions()[$match->status] ?? ucfirst($match->status) }}</span>
                                </div>
                                <div class="match-line {{ $match->winner_id === $match->player1_id && $match->winner_id ? 'winner' : '' }}">
                                    <span>{{ $match->player1?->full_name ?: 'BYE' }}</span>
                                    <span class="match-score">{{ $match->player1_frames }}</span>
                                </div>
                                <div class="match-line {{ $match->winner_id === $match->player2_id && $match->winner_id ? 'winner' : '' }}">
                                    <span>{{ $match->player2?->full_name ?: 'BYE' }}</span>
                                    <span class="match-score">{{ $match->player2_frames }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @empty
                    <div class="card">
                        <h3>Draw has not been generated yet</h3>
                        <p class="muted">Generate the draw from admin to show public draw and results.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Registered Players</h2>
                    <p>Seeds, club details, payment status, and player performance.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Seed</th>
                            <th>Player</th>
                            <th>Club</th>
                            <th>Reg #</th>
                            <th>Fee</th>
                            <th>Played</th>
                            <th>Won</th>
                            <th>High break</th>
                        </tr>
                    </thead>
                    <tbody data-live-players-body>
                        @forelse ($tournament->players as $player)
                            <tr>
                                <td>{{ $player->seed ?: '-' }}</td>
                                <td>{{ $player->full_name }}</td>
                                <td>{{ $player->club_name ?: '-' }}</td>
                                <td>{{ $player->registration_number }}</td>
                                <td><span class="badge {{ $player->fee_status === 'paid' ? '' : 'red' }}">{{ \App\Models\TournamentPlayer::feeStatusOptions()[$player->fee_status] ?? ucfirst($player->fee_status) }}</span></td>
                                <td>{{ $player->matches_played }}</td>
                                <td>{{ $player->matches_won }}</td>
                                <td>{{ $player->highest_break }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">No players registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Matches</h2>
                    <p>Draw and match results are updated from the tournament match desk.</p>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Round</th>
                            <th>Match</th>
                            <th>Table</th>
                            <th>Player 1</th>
                            <th>Player 2</th>
                            <th>Score</th>
                            <th>Winner</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody data-live-matches-body>
                        @forelse ($tournament->matches->sortBy(['round_number', 'match_number']) as $match)
                            <tr>
                                <td>{{ $match->round_name }}</td>
                                <td>#{{ $match->match_number }}</td>
                                <td>{{ $match->table_number ?: '-' }}</td>
                                <td>{{ $match->player1?->full_name ?: 'BYE' }}</td>
                                <td>{{ $match->player2?->full_name ?: 'BYE' }}</td>
                                <td>{{ $match->score_label }}</td>
                                <td>{{ $match->winner?->full_name ?: '-' }}</td>
                                <td><span class="badge">{{ \App\Models\TournamentMatch::statusOptions()[$match->status] ?? ucfirst($match->status) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">Draw has not been generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <script>
        (() => {
            const root = document.querySelector('[data-live-tournament]');
            if (!root) return;

            const fields = {
                status: root.querySelector('[data-live-status]'),
                title: root.querySelector('[data-live-title]'),
                summary: root.querySelector('[data-live-summary]'),
                playerCount: root.querySelector('[data-live-player-count]'),
                updated: root.querySelector('[data-live-updated]'),
                drawBody: document.querySelector('[data-live-draw-body]'),
                playersBody: document.querySelector('[data-live-players-body]'),
                matchesBody: document.querySelector('[data-live-matches-body]'),
            };
            let signature = '';

            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char]);

            const renderPlayers = (players) => {
                fields.playersBody.innerHTML = players.length ? players.map((player) => `
                    <tr>
                        <td>${escapeHtml(player.seed || '-')}</td>
                        <td>${escapeHtml(player.name)}</td>
                        <td>${escapeHtml(player.club || '-')}</td>
                        <td>${escapeHtml(player.registration_number || '-')}</td>
                        <td><span class="badge ${player.fee_status === 'paid' ? '' : 'red'}">${escapeHtml(player.fee_status_label)}</span></td>
                        <td>${escapeHtml(player.matches_played)}</td>
                        <td>${escapeHtml(player.matches_won)}</td>
                        <td>${escapeHtml(player.highest_break)}</td>
                    </tr>
                `).join('') : '<tr><td colspan="8">No players registered yet.</td></tr>';
            };

            const renderDraw = (matches) => {
                if (!matches.length) {
                    fields.drawBody.innerHTML = `
                        <div class="card">
                            <h3>Draw has not been generated yet</h3>
                            <p class="muted">Generate the draw from admin to show public draw and results.</p>
                        </div>
                    `;
                    return;
                }

                const rounds = matches.reduce((carry, match) => {
                    carry[match.round_number] ??= {
                        name: match.round_name,
                        matches: [],
                    };
                    carry[match.round_number].matches.push(match);

                    return carry;
                }, {});

                fields.drawBody.innerHTML = Object.values(rounds).map((round) => `
                    <div class="draw-round">
                        <h3>${escapeHtml(round.name)}</h3>
                        ${round.matches.map((match) => `
                            <article class="match-card ${escapeHtml(match.status)}">
                                <div class="match-head">
                                    <span>Match #${escapeHtml(match.match_number)}</span>
                                    <span>${escapeHtml(match.status_label)}</span>
                                </div>
                                <div class="match-line ${match.winner && match.winner === match.player1 ? 'winner' : ''}">
                                    <span>${escapeHtml(match.player1)}</span>
                                    <span class="match-score">${escapeHtml(String(match.score).split(' - ')[0] ?? 0)}</span>
                                </div>
                                <div class="match-line ${match.winner && match.winner === match.player2 ? 'winner' : ''}">
                                    <span>${escapeHtml(match.player2)}</span>
                                    <span class="match-score">${escapeHtml(String(match.score).split(' - ')[1] ?? 0)}</span>
                                </div>
                            </article>
                        `).join('')}
                    </div>
                `).join('');
            };

            const renderMatches = (matches) => {
                fields.matchesBody.innerHTML = matches.length ? matches.map((match) => `
                    <tr>
                        <td>${escapeHtml(match.round_name)}</td>
                        <td>#${escapeHtml(match.match_number)}</td>
                        <td>${escapeHtml(match.table_number || '-')}</td>
                        <td>${escapeHtml(match.player1)}</td>
                        <td>${escapeHtml(match.player2)}</td>
                        <td><strong>${escapeHtml(match.score)}</strong></td>
                        <td>${escapeHtml(match.winner || '-')}</td>
                        <td><span class="badge">${escapeHtml(match.status_label)}</span></td>
                    </tr>
                `).join('') : '<tr><td colspan="8">Draw has not been generated yet.</td></tr>';
            };

            const render = (payload) => {
                if (!payload.tournament) return;

                const tournament = payload.tournament;
                const matches = payload.matches ?? [];
                const players = payload.players ?? [];
                const nextSignature = `${payload.last_updated_at}|${matches.map((match) => `${match.id}:${match.score}:${match.status}:${match.winner ?? ''}`).join('|')}`;
                const changed = signature && signature !== nextSignature;
                signature = nextSignature;

                fields.status.textContent = tournament.status_label;
                fields.title.textContent = tournament.name;
                fields.summary.textContent = `${tournament.type_label} · ${tournament.match_format_label} · ${tournament.starts_at ?? 'Date not set'}`;
                fields.playerCount.textContent = tournament.players_count;
                fields.updated.textContent = payload.last_updated_at ? `Updated: ${new Date(payload.last_updated_at).toLocaleTimeString()}` : 'Live updates enabled';

                renderPlayers(players);
                renderDraw(matches);
                renderMatches(matches);

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
