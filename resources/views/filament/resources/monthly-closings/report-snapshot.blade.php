@php
    $month = $get('month') ?: $record?->month;
    $monthLabel = $month ? \Illuminate\Support\Carbon::parse($month)->format('F Y') : 'Monthly closing';
@endphp

<div class="summit-monthly-closing-snapshot">
    <div class="summit-monthly-closing-snapshot-header">
        <div>
            <div class="summit-monthly-closing-snapshot-title">Printable closing snapshot</div>
            <div class="summit-monthly-closing-snapshot-subtitle">{{ $monthLabel }}</div>
        </div>

        <button
            type="button"
            onclick="document.body.classList.add('summit-print-monthly-closing-snapshot-only'); window.addEventListener('afterprint', () => document.body.classList.remove('summit-print-monthly-closing-snapshot-only'), { once: true }); window.print(); setTimeout(() => document.body.classList.remove('summit-print-monthly-closing-snapshot-only'), 1200)"
            class="summit-print-button summit-print-button-secondary"
        >
            Print snapshot
        </button>
    </div>

    <div class="summit-monthly-closing-snapshot-list">
        {{ $getChildSchema() }}
    </div>
</div>
