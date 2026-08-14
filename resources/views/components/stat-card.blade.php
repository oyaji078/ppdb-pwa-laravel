@props([
    'label',
    'value',
    'icon' => 'chart-column',
    'caption' => null,
    'tone' => 'brand',
    'progress' => null,
    'href' => null,
])

@php
    $tones = [
        'brand' => ['bg-brand-50', 'text-brand-600', 'bg-brand-600'],
        'success' => ['bg-emerald-50', 'text-emerald-600', 'bg-emerald-500'],
        'warning' => ['bg-amber-50', 'text-amber-600', 'bg-amber-500'],
        'danger' => ['bg-rose-50', 'text-rose-600', 'bg-rose-500'],
        'info' => ['bg-indigo-50', 'text-indigo-600', 'bg-indigo-500'],
        'neutral' => ['bg-slate-100', 'text-slate-600', 'bg-slate-500'],
    ];

    [$iconBg, $iconColor, $barColor] = $tones[$tone] ?? $tones['brand'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card block p-5'.($href ? ' transition-shadow hover:shadow-md' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ $value }}</p>
        </div>

        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconBg }} {{ $iconColor }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>

    @if ($progress !== null)
        <div class="mt-3">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100"
                 role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ $label }}">
                <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ max(0, min(100, $progress)) }}%"></div>
            </div>
        </div>
    @endif

    @if ($caption)
        <p class="mt-2 truncate text-xs text-slate-500">{{ $caption }}</p>
    @endif
</{{ $tag }}>
