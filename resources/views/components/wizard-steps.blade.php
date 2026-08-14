@props(['current', 'registration' => null])

@php
    $steps = [
        ['key' => 'pendaftaran', 'label' => 'Data Pendaftaran', 'route' => 'registration.start'],
        ['key' => 'biodata', 'label' => 'Biodata', 'route' => 'registration.biodata'],
        ['key' => 'alamat', 'label' => 'Alamat', 'route' => 'registration.address'],
        ['key' => 'orang-tua', 'label' => 'Orang Tua/Wali', 'route' => 'registration.parents'],
        ['key' => 'asal-sekolah', 'label' => 'Asal Sekolah', 'route' => 'registration.previous-school'],
        ['key' => 'program', 'label' => 'Program', 'route' => 'registration.program'],
        ['key' => 'berkas', 'label' => 'Berkas', 'route' => 'registration.documents'],
        ['key' => 'review', 'label' => 'Review', 'route' => 'registration.review'],
    ];

    $keys = array_column($steps, 'key');
    $currentIndex = array_search($current, $keys, true) ?: 0;

    // A step is reachable once the draft has progressed at least that far.
    $reachedIndex = $registration
        ? max((int) array_search($registration->current_step, $keys, true), $currentIndex)
        : $currentIndex;

    $currentStep = $steps[$currentIndex];
@endphp

<nav aria-label="Langkah pendaftaran" class="card p-4 sm:p-5">
    {{-- Mobile: compact "3 / 8 — Alamat" --}}
    <div class="sm:hidden">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-slate-900">
                <span class="text-brand-600">{{ $currentIndex + 1 }}</span>
                <span class="text-slate-400">/ {{ count($steps) }}</span>
                <span class="ml-1.5">{{ $currentStep['label'] }}</span>
            </p>
            <p class="text-xs text-slate-500">{{ round(($currentIndex + 1) / count($steps) * 100) }}%</p>
        </div>

        <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-brand-600 transition-all"
                 style="width: {{ ($currentIndex + 1) / count($steps) * 100 }}%"></div>
        </div>
    </div>

    {{-- Desktop / tablet: full stepper --}}
    <ol class="hidden gap-1 sm:flex sm:flex-wrap lg:flex-nowrap">
        @foreach ($steps as $index => $step)
            @php
                $isCurrent = $index === $currentIndex;
                $isDone = $index < $reachedIndex && ! $isCurrent;
                $isReachable = $index <= $reachedIndex && $registration !== null;
            @endphp

            <li class="min-w-0 flex-1">
                @if ($isReachable && ! $isCurrent)
                    <a href="{{ route($step['route']) }}" class="group block rounded-lg p-2 hover:bg-slate-50">
                        @include('partials.wizard-step', ['step' => $step, 'index' => $index, 'isCurrent' => $isCurrent, 'isDone' => $isDone])
                    </a>
                @else
                    <div class="rounded-lg p-2" @if ($isCurrent) aria-current="step" @endif>
                        @include('partials.wizard-step', ['step' => $step, 'index' => $index, 'isCurrent' => $isCurrent, 'isDone' => $isDone])
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
