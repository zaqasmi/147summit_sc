@extends('website.layout')

@section('title', ($page->meta_title ?: $page->title).' | '.($settings['site_name'] ?? '147 Summit Snooker Club'))
@section('description', $page->meta_description ?: $page->excerpt ?: $page->title)

@section('content')
    <section>
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="badge">{{ \App\Models\CmsPage::sectionOptions()[$page->section] ?? ucfirst($page->section) }}</span>
                    <h2 style="margin-top: 12px;">{{ $page->title }}</h2>
                    @if ($page->excerpt)
                        <p>{{ $page->excerpt }}</p>
                    @endif
                </div>
            </div>

            <article class="card">
                {!! nl2br(e($page->content)) !!}
            </article>
        </div>
    </section>
@endsection
