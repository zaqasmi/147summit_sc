@extends('website.layout')

@section('title', $about['title'].' | '.($settings['site_name'] ?? '147 Summit Snooker Club'))
@section('description', $about['excerpt'])

@section('content')
    <section class="hero has-image" style="background-image: url('{{ asset('website-assets/gallery/club-wide-room.jpeg') }}')">
        <div class="wrap hero-inner">
            <div>
                <div class="eyebrow">Gilgit-Baltistan snooker development</div>
                <h1>{{ $about['title'] }}</h1>
                <p>{{ $about['excerpt'] }}</p>
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="live-board">
                <article class="card">
                    <h2>Club Mission</h2>
                    <p class="muted">{{ $about['content'] }}</p>
                </article>

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
@endsection
