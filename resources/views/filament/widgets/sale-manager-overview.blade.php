@php
    $toneClasses = [
        'success' => [
            'panel' => 'summit-dashboard-stat-success',
            'icon' => 'summit-dashboard-icon-success',
            'value' => 'summit-dashboard-value-success',
        ],
        'danger' => [
            'panel' => 'summit-dashboard-stat-danger',
            'icon' => 'summit-dashboard-icon-danger',
            'value' => 'summit-dashboard-value-danger',
        ],
        'warning' => [
            'panel' => 'summit-dashboard-stat-warning',
            'icon' => 'summit-dashboard-icon-warning',
            'value' => 'summit-dashboard-value-warning',
        ],
        'info' => [
            'panel' => 'summit-dashboard-stat-info',
            'icon' => 'summit-dashboard-icon-info',
            'value' => 'summit-dashboard-value-info',
        ],
        'gray' => [
            'panel' => 'summit-dashboard-stat-gray',
            'icon' => 'summit-dashboard-icon-gray',
            'value' => 'summit-dashboard-value-gray',
        ],
    ];
@endphp

<x-filament-widgets::widget class="summit-dashboard">
    <div class="summit-dashboard-shell">
        <div class="summit-dashboard-header">
            <div>
                <p class="summit-dashboard-eyebrow">Read only dashboard</p>
                <h2 class="summit-dashboard-title">Daily snapshot</h2>
                <p class="summit-dashboard-copy">
                    A clear view of sale, expense, advance paid, commission, and pending balances.
                </p>
            </div>

            <div class="summit-dashboard-date">
                <x-filament::icon icon="heroicon-o-calendar-days" class="summit-dashboard-date-icon" />
                <span>{{ $asOfLabel }}</span>
            </div>
        </div>

        <div class="summit-dashboard-hero-grid">
            @foreach ($heroStats as $stat)
                @php($tone = $toneClasses[$stat['color']] ?? $toneClasses['gray'])

                <article class="summit-dashboard-hero-card {{ $tone['panel'] }}">
                    <div class="summit-dashboard-stat-topline">
                        <span class="summit-dashboard-icon {{ $tone['icon'] }}">
                            <x-filament::icon :icon="$stat['icon']" />
                        </span>
                        <span class="summit-dashboard-label">{{ $stat['label'] }}</span>
                    </div>
                    <strong class="summit-dashboard-hero-value {{ $tone['value'] }}">{{ $stat['value'] }}</strong>
                    <p class="summit-dashboard-description">{{ $stat['description'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="summit-dashboard-section-grid">
            @foreach ($sections as $section)
                <section class="summit-dashboard-section">
                    <div class="summit-dashboard-section-header">
                        <div>
                            <h3>{{ $section['title'] }}</h3>
                            <p>{{ $section['description'] }}</p>
                        </div>
                    </div>

                    <div class="summit-dashboard-mini-grid">
                        @foreach ($section['stats'] as $stat)
                            @php($tone = $toneClasses[$stat['color']] ?? $toneClasses['gray'])

                            <article class="summit-dashboard-mini-card">
                                <span class="summit-dashboard-icon {{ $tone['icon'] }}">
                                    <x-filament::icon :icon="$stat['icon']" />
                                </span>
                                <div class="summit-dashboard-mini-body">
                                    <span class="summit-dashboard-label">{{ $stat['label'] }}</span>
                                    <strong class="{{ $tone['value'] }}">{{ $stat['value'] }}</strong>
                                    <p>{{ $stat['description'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-widgets::widget>
