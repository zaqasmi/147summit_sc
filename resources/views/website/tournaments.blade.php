@extends('website.layout')

@section('title', 'Tournaments | '.($settings['site_name'] ?? '147 Summit Snooker Club'))

@section('content')
    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>Tournaments</h2>
                    <p>Published tournaments with registrations, draw status, and match progress.</p>
                </div>
            </div>

            <div class="grid three">
                @forelse ($tournaments as $tournament)
                    <article class="card">
                        <span class="badge">
                            {{ \App\Models\Tournament::statusOptions()[$tournament->status] ?? ucfirst($tournament->status) }}
                        </span>
                        <h3 style="margin-top: 14px;">
                            <a href="{{ route('website.tournament', $tournament) }}">{{ $tournament->name }}</a>
                        </h3>
                        <div class="meta">
                            {{ $tournament->starts_at?->format('d M Y') ?? 'Date not set' }}
                            · {{ \App\Models\Tournament::typeOptions()[$tournament->type] ?? ucfirst($tournament->type) }}
                        </div>
                        <p class="muted">
                            Players: {{ $tournament->players_count ?? $tournament->registered_players_count }}
                            · Matches: {{ $tournament->matches_count ?? 0 }}
                            · Fee: Rs {{ number_format((float) $tournament->registration_fee, 2) }}
                        </p>
                    </article>
                @empty
                    <div class="card">
                        <h3>No published tournaments yet</h3>
                        <p class="muted">Publish tournaments from the admin panel to list them here.</p>
                    </div>
                @endforelse
            </div>

            <div class="pagination">{{ $tournaments->links() }}</div>
        </div>
    </section>
@endsection
