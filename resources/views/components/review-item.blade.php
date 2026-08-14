@props(['label', 'value' => null, 'wide' => false])

<div @class(['sm:col-span-2' => $wide])>
    <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
    <dd class="mt-0.5 text-sm break-words text-slate-900">
        {{ filled($value) ? $value : '-' }}
    </dd>
</div>
