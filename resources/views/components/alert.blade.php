@props(['type' => 'info', 'title' => null])

@php
    $styles = [
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200', 'circle-check', 'text-emerald-600'],
        'error' => ['bg-rose-50 text-rose-800 ring-rose-200', 'circle-alert', 'text-rose-600'],
        'warning' => ['bg-amber-50 text-amber-900 ring-amber-200', 'triangle-alert', 'text-amber-600'],
        'info' => ['bg-blue-50 text-blue-800 ring-blue-200', 'info', 'text-blue-600'],
    ];

    [$classes, $icon, $iconColor] = $styles[$type] ?? $styles['info'];
@endphp

<div role="alert" {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg p-4 text-sm ring-1 ring-inset '.$classes]) }}>
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0 {{ $iconColor }}" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
    </div>
</div>
