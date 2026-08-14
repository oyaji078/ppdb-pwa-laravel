@props(['name', 'label', 'value' => '1', 'checked' => false, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $isChecked = (bool) old($name, $checked);
@endphp

<div>
    <div class="flex items-start gap-3">
        <input
            type="checkbox"
            name="{{ $name }}"
            id="{{ $id }}"
            value="{{ $value }}"
            @checked($isChecked)
            {{ $attributes->merge(['class' => 'mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-600']) }}
        >
        <label for="{{ $id }}" class="text-sm text-slate-700">
            {{ $label }}
            @if ($hint)
                <span class="mt-0.5 block text-xs text-slate-500">{{ $hint }}</span>
            @endif
        </label>
    </div>

    @error($name)
        <p class="form-error">
            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
