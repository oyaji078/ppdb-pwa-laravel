@props(['title', 'edit' => null])

<section class="card overflow-hidden">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
        <h2 class="font-semibold text-slate-900">{{ $title }}</h2>

        @if ($edit)
            <a href="{{ $edit }}" class="btn-secondary btn-sm">
                <x-icon name="pencil" class="h-3.5 w-3.5" />
                Edit
            </a>
        @endif
    </div>

    <dl class="grid gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2">
        {{ $slot }}
    </dl>
</section>
