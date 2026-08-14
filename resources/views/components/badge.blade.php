@props(['class' => 'bg-slate-100 text-slate-700 ring-slate-200', 'icon' => null])

<span {{ $attributes->merge(['class' => 'badge '.$class]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-3.5 w-3.5" />
    @endif
    {{ $slot }}
</span>
