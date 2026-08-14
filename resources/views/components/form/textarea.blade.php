@props([
    'name',
    'label' => null,
    'value' => null,
    'required' => false,
    'rows' => 4,
    'hint' => null,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', $name);
    $errorId = $id.'-error';
    $hintId = $id.'-hint';
    $hasError = $errors->has($name);
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-rose-600" aria-hidden="true">*</span>
                <span class="sr-only">wajib diisi</span>
            @endif
        </label>
    @endif

    <textarea
        name="{{ $name }}"
        id="{{ $id }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        aria-describedby="{{ trim(($hasError ? $errorId.' ' : '').($hint ? $hintId : '')) ?: null }}"
        {{ $attributes->merge(['class' => 'form-input'.($hasError ? ' ring-rose-400 focus:ring-rose-500' : '')]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($hint)
        <p id="{{ $hintId }}" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $errorId }}" class="form-error">
            <x-icon name="circle-alert" class="mt-px h-3.5 w-3.5 shrink-0" />
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
