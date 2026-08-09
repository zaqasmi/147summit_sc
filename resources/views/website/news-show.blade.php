@extends('website.layout')

@php
    use Illuminate\Support\Str;

    $image = filled($post->cover_image_path)
        ? (Str::startsWith($post->cover_image_path, ['http://', 'https://', '/']) ? $post->cover_image_path : asset('storage/'.ltrim($post->cover_image_path, '/')))
        : null;
@endphp

@section('title', $post->title.' | '.($settings['site_name'] ?? '147 Summit Snooker Club'))
@section('description', $post->excerpt ?: $post->title)

@section('content')
    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="badge">{{ $post->published_at?->format('d M Y') ?? 'News' }}</span>
                    <h2 style="margin-top: 12px;">{{ $post->title }}</h2>
                    @if ($post->excerpt)
                        <p>{{ $post->excerpt }}</p>
                    @endif
                </div>
            </div>

            @if ($image)
                <div class="media" style="aspect-ratio: 18 / 7; margin-bottom: 24px;">
                    <img src="{{ $image }}" alt="{{ $post->title }}">
                </div>
            @endif

            <article class="card">
                {!! nl2br(e($post->body)) !!}
            </article>
        </div>
    </section>
@endsection
