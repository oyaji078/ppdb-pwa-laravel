@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
    // Shows the asterisk without the browser's own required check. For fields
    // governed by a rule the browser cannot express — "at least one of these
    // three" — where `required` would block a submission the server accepts.
    'indicate' => null,
    'hint' => null,
    'placeholder' => null,
])

@php
    $id = $attributes->get('id', str_replace('.', '_', $name));
    $errorId = $id.'-error';
    $hintId = $id.'-hint';
    $hasError = $errors->has($name);
    $marked = $indicate ?? $required;
@endphp

<div>
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}
            @if ($marked)
                <span class="text-rose-600" aria-hidden="true">*</span>
                <span class="sr-only">wajib diisi</span>
            @endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ field_name($name) }}"
        id="{{ $id }}"
        value="{{ old($name, $value) }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required aria-required="true" @endif
        @if ($hasError) aria-invalid="true" @endif
        aria-describedby="{{ trim(($hasError ? $errorId.' ' : '').($hint ? $hintId : '')) ?: null }}"
        {{ $attributes->except('id')->merge(['class' => 'form-input'.($hasError ? ' ring-rose-400 focus:ring-rose-500' : '')]) }}
    >

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
