<div class="flex items-center gap-2">
    <span @class([
        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
        'bg-brand-600 text-white' => $isCurrent,
        'bg-emerald-100 text-emerald-700' => $isDone,
        'bg-slate-100 text-slate-500' => ! $isCurrent && ! $isDone,
    ])>
        @if ($isDone)
            <x-icon name="check" class="h-3.5 w-3.5" />
        @else
            {{ $index + 1 }}
        @endif
    </span>

    <span @class([
        'min-w-0 truncate text-xs font-medium',
        'text-brand-700' => $isCurrent,
        'text-slate-700' => $isDone,
        'text-slate-400' => ! $isCurrent && ! $isDone,
    ])>{{ $step['label'] }}</span>
</div>
