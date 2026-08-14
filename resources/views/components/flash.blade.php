{{-- Session flash messages, rendered once per page below the header. --}}

@if (session('success'))
    <x-alert type="success" class="mb-5">{{ session('success') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error" class="mb-5">{{ session('error') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning" class="mb-5">{{ session('warning') }}</x-alert>
@endif

@if (session('info'))
    <x-alert type="info" class="mb-5">{{ session('info') }}</x-alert>
@endif

@if ($errors->any() && ! $errors->hasBag('default_hidden'))
    <x-alert type="error" title="Periksa kembali isian Anda" class="mb-5">
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
