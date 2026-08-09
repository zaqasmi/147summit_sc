@php
    $siteName = $settings['site_name'] ?? config('app.name', '147 Summit Snooker Club');
    $tagline = $settings['site_tagline'] ?? 'Tournament snooker, club events, and player management.';
    $phone = $settings['contact_phone'] ?? null;
    $email = $settings['contact_email'] ?? null;
    $address = $settings['club_address'] ?? null;
    $logoUrl = asset('website-assets/147-summit-logo.jpeg');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', $siteName)</title>
        <meta name="description" content="@yield('description', $tagline)">
        <style>
            :root {
                --ink: #121715;
                --muted: #59635f;
                --surface: #ffffff;
                --line: #d9dedb;
                --paper: #f5f3ee;
                --green: #174f3d;
                --green-2: #0f332a;
                --red: #7f1d2d;
                --gold: #c8952e;
            }

            * { box-sizing: border-box; }
            body {
                margin: 0;
                color: var(--ink);
                background: var(--paper);
                font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                line-height: 1.5;
            }

            a { color: inherit; text-decoration: none; }
            img { display: block; max-width: 100%; }
            .wrap { width: min(1160px, calc(100% - 32px)); margin: 0 auto; }
            .site-header {
                position: sticky;
                top: 0;
                z-index: 20;
                border-bottom: 1px solid rgba(255, 255, 255, .14);
                background: rgba(18, 23, 21, .94);
                color: #fff;
                backdrop-filter: blur(12px);
            }
            .nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-height: 68px;
                gap: 20px;
            }
            .brand {
                display: inline-flex;
                align-items: center;
                gap: 10px;
                font-weight: 800;
                font-size: 1.05rem;
            }
            .brand img {
                width: 42px;
                height: 42px;
                border-radius: 6px;
                object-fit: cover;
                background: #050505;
            }
            .links { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; font-size: .92rem; color: rgba(255, 255, 255, .82); }
            .links a:hover { color: #fff; }
            .admin-link {
                border: 1px solid rgba(255, 255, 255, .24);
                border-radius: 6px;
                padding: 8px 12px;
                color: #fff;
            }
            .hero {
                position: relative;
                min-height: 520px;
                color: #fff;
                background: linear-gradient(135deg, var(--green-2), #243d35 58%, var(--red));
                overflow: hidden;
            }
            .hero.has-image {
                background-size: cover;
                background-position: center;
            }
            .hero::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, rgba(9, 18, 15, .88), rgba(9, 18, 15, .52) 54%, rgba(9, 18, 15, .14));
            }
            .hero-inner {
                position: relative;
                display: grid;
                align-content: end;
                min-height: 520px;
                padding: 96px 0 54px;
            }
            .eyebrow { color: #f0ca75; font-weight: 700; text-transform: uppercase; font-size: .78rem; }
            h1 { max-width: 760px; margin: 10px 0 16px; font-size: clamp(2.45rem, 6vw, 5rem); line-height: .95; letter-spacing: 0; }
            .hero p { max-width: 650px; margin: 0; color: rgba(255, 255, 255, .82); font-size: 1.08rem; }
            .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 28px; }
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                border-radius: 6px;
                padding: 0 16px;
                font-weight: 700;
                border: 1px solid transparent;
            }
            .btn.primary { background: var(--gold); color: #1d1609; }
            .btn.secondary { color: #fff; border-color: rgba(255, 255, 255, .32); }
            section { padding: 58px 0; }
            .section-head {
                display: flex;
                justify-content: space-between;
                gap: 20px;
                align-items: end;
                margin-bottom: 22px;
            }
            h2 { margin: 0; font-size: clamp(1.6rem, 3vw, 2.4rem); line-height: 1.1; }
            .section-head p, .muted { color: var(--muted); margin: 6px 0 0; }
            .grid { display: grid; gap: 16px; }
            .grid.three { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .grid.four { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .card {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 8px;
                padding: 18px;
                min-width: 0;
            }
            .card h3 { margin: 0 0 8px; font-size: 1.1rem; }
            .meta { color: var(--muted); font-size: .9rem; }
            .badge {
                display: inline-flex;
                width: fit-content;
                border-radius: 999px;
                padding: 4px 10px;
                font-size: .78rem;
                font-weight: 700;
                background: #e8f1ed;
                color: var(--green);
            }
            .badge.red { background: #f7e4e8; color: var(--red); }
            .live-board {
                display: grid;
                grid-template-columns: minmax(0, 1fr) minmax(320px, .9fr);
                gap: 16px;
                align-items: start;
            }
            .live-dot {
                display: inline-block;
                width: 9px;
                height: 9px;
                border-radius: 999px;
                margin-right: 8px;
                background: #22c55e;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, .14);
            }
            .live-meta {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-top: 14px;
            }
            .live-meta span {
                border: 1px solid var(--line);
                border-radius: 6px;
                padding: 8px 10px;
                background: #fff;
                color: var(--muted);
                font-size: .9rem;
            }
            .live-flash { animation: liveFlash .65s ease-out; }
            @keyframes liveFlash {
                from { background: #fff6d8; }
                to { background: transparent; }
            }
            .media {
                aspect-ratio: 16 / 10;
                background: #dfe5e1;
                border-radius: 8px;
                overflow: hidden;
                margin-bottom: 12px;
            }
            .media img { width: 100%; height: 100%; object-fit: cover; }
            .draw-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(260px, 1fr));
                gap: 16px;
                overflow-x: auto;
                padding-bottom: 6px;
            }
            .draw-round {
                display: grid;
                align-content: start;
                gap: 12px;
                min-width: 260px;
            }
            .draw-round h3 {
                margin: 0;
                color: var(--green-2);
                font-size: 1rem;
            }
            .match-card {
                border: 1px solid var(--line);
                border-left: 4px solid var(--green);
                border-radius: 8px;
                background: #fff;
                padding: 12px;
            }
            .match-card.completed { border-left-color: var(--gold); }
            .match-card.ongoing { border-left-color: #22c55e; }
            .match-card .match-head {
                display: flex;
                justify-content: space-between;
                gap: 10px;
                color: var(--muted);
                font-size: .82rem;
                margin-bottom: 8px;
            }
            .match-line {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 12px;
                padding: 6px 0;
                border-top: 1px solid #edf0ed;
            }
            .match-line:first-of-type { border-top: 0; }
            .match-line.winner { color: var(--green); font-weight: 800; }
            .match-score {
                font-weight: 800;
                color: var(--ink);
            }
            .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
            table { width: 100%; border-collapse: collapse; min-width: 760px; }
            th, td { padding: 12px 14px; border-bottom: 1px solid var(--line); text-align: left; }
            th { background: #f0f2ef; font-size: .82rem; text-transform: uppercase; color: #39413d; }
            tr:last-child td { border-bottom: 0; }
            .contact-band { background: var(--green-2); color: #fff; }
            .contact-band .muted { color: rgba(255, 255, 255, .72); }
            .contact-form { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
            input, textarea {
                width: 100%;
                border: 1px solid var(--line);
                border-radius: 6px;
                padding: 12px;
                background: #fff;
                color: var(--ink);
            }
            textarea { min-height: 118px; resize: vertical; }
            .span-2 { grid-column: span 2; }
            .site-footer { padding: 28px 0; color: rgba(255, 255, 255, .72); background: #121715; }
            .footer-row { display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
            .pagination { margin-top: 24px; }

            @media (max-width: 860px) {
                .nav { align-items: flex-start; flex-direction: column; padding: 14px 0; }
                .grid.three, .grid.four { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .draw-grid { grid-template-columns: 1fr; }
                .live-board { grid-template-columns: 1fr; }
                .section-head { align-items: flex-start; flex-direction: column; }
            }

            @media (max-width: 620px) {
                .wrap { width: min(100% - 24px, 1160px); }
                .hero, .hero-inner { min-height: 500px; }
                .grid.three, .grid.four, .contact-form { grid-template-columns: 1fr; }
                .span-2 { grid-column: auto; }
            }
        </style>
    </head>
    <body>
        <header class="site-header">
            <div class="wrap nav">
                <a class="brand" href="{{ route('website.home') }}">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }} logo">
                    <span>{{ $siteName }}</span>
                </a>
                <nav class="links" aria-label="Main navigation">
                    <a href="{{ route('website.home') }}">Home</a>
                    <a href="{{ route('website.about') }}">About</a>
                    <a href="{{ route('website.tournaments') }}">Tournaments</a>
                    <a href="{{ route('website.home') }}#news">News</a>
                    <a href="{{ route('website.home') }}#gallery">Gallery</a>
                    <a href="{{ route('website.home') }}#contact">Contact</a>
                    <a class="admin-link" href="/admin">Admin</a>
                </nav>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="wrap footer-row">
                <div>
                    <strong>{{ $siteName }}</strong>
                    <div>{{ $tagline }}</div>
                </div>
                <div>
                    @if ($phone)<div>{{ $phone }}</div>@endif
                    @if ($email)<div>{{ $email }}</div>@endif
                    @if ($address)<div>{{ $address }}</div>@endif
                </div>
            </div>
        </footer>
    </body>
</html>
