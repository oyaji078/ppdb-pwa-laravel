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

    // Password fields get a reveal toggle. Typing a password you cannot see, on
    // a phone keyboard, is where sign-ups get abandoned.
    $isPassword = $type === 'password';

    $classes = 'form-input'
        .($hasError ? ' ring-rose-400 focus:ring-rose-500' : '')
        .($isPassword ? ' pr-11' : '');
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

    {{-- The field stays type="password" in the markup and is switched by script,
         so with JavaScript off it simply remains masked rather than exposed. --}}
    <div @if ($isPassword) x-data="{ shown: false }" class="relative" @endif>
        <input
            type="{{ $type }}"
            name="{{ field_name($name) }}"
            id="{{ $id }}"
            value="{{ old($name, $value) }}"
            @if ($isPassword) x-ref="field" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($required) required aria-required="true" @endif
            @if ($hasError) aria-invalid="true" @endif
            aria-describedby="{{ trim(($hasError ? $errorId.' ' : '').($hint ? $hintId : '')) ?: null }}"
            {{ $attributes->except('id')->merge(['class' => $classes]) }}
        >

        @if ($isPassword)
            <button type="button"
                    @click="shown = ! shown; $refs.field.type = shown ? 'text' : 'password'"
                    :aria-label="shown ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                    :aria-pressed="shown.toString()"
                    tabindex="-1"
                    class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3 text-slate-400 hover:text-slate-700 focus:outline-none focus-visible:text-slate-700">
                {{-- Alpine sits on the span, not the <i>: Lucide swaps the <i>
                     for an <svg> and would take x-show with it. --}}
                <span x-show="! shown"><x-icon name="eye" class="h-4 w-4" /></span>
                <span x-show="shown" x-cloak><x-icon name="eye-off" class="h-4 w-4" /></span>
            </button>
        @endif
    </div>

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
