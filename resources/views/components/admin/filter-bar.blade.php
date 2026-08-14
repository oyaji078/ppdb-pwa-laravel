@props([
    'action',
    'academicYears' => null,
    'waves' => null,
    'tracks' => null,
    'programs' => null,
    'registrationStatuses' => null,
    'selectionStatuses' => null,
    'reregistrationStatuses' => null,
    'decisionOptions' => null,
    'searchPlaceholder' => 'Cari nomor pendaftaran, nama, NISN, atau asal sekolah',
])

@php
    $hasActiveFilter = collect(request()->query())
        ->except(['page', 'per_page'])
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->isNotEmpty();
@endphp

<form method="GET" action="{{ $action }}" class="card p-4" x-data="{ expanded: {{ $hasActiveFilter ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="min-w-0 flex-1">
            <label for="q" class="sr-only">Cari pendaftar</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" name="q" id="q" value="{{ request('q') }}"
                       placeholder="{{ $searchPlaceholder }}" class="form-input pl-9">
            </div>
        </div>

        <div class="flex shrink-0 gap-2">
            <button type="submit" class="btn-primary">
                <x-icon name="search" class="h-4 w-4" />
                Cari
            </button>

            <button type="button" @click="expanded = ! expanded" class="btn-secondary" :aria-expanded="expanded.toString()">
                <x-icon name="sliders-horizontal" class="h-4 w-4" />
                Filter
            </button>

            @if ($hasActiveFilter)
                <a href="{{ $action }}" class="btn-ghost" title="Bersihkan filter">
                    <x-icon name="x" class="h-4 w-4" />
                    <span class="sr-only">Bersihkan filter</span>
                </a>
            @endif
        </div>
    </div>

    <div x-show="expanded" x-cloak x-collapse>
        <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-4">
            @if ($academicYears)
                <x-form.select name="academic_year_id" label="Tahun Ajaran" placeholder="Semua tahun"
                               :value="request('academic_year_id')"
                               :options="$academicYears->pluck('name', 'id')->all()" />
            @endif

            @if ($waves)
                <x-form.select name="registration_wave_id" label="Gelombang" placeholder="Semua gelombang"
                               :value="request('registration_wave_id')"
                               :options="$waves->pluck('name', 'id')->all()" />
            @endif

            @if ($tracks)
                <x-form.select name="admission_track_id" label="Jalur" placeholder="Semua jalur"
                               :value="request('admission_track_id')"
                               :options="$tracks->pluck('name', 'id')->all()" />
            @endif

            @if ($programs)
                <x-form.select name="program_id" label="Program" placeholder="Semua program"
                               :value="request('program_id')"
                               :options="$programs->pluck('name', 'id')->all()" />
            @endif

            @if ($registrationStatuses)
                <x-form.select name="registration_status" label="Status Pendaftaran" placeholder="Semua status"
                               :value="request('registration_status')" :options="$registrationStatuses" />
            @endif

            @if ($selectionStatuses)
                <x-form.select name="selection_status" label="Status Seleksi" placeholder="Semua status"
                               :value="request('selection_status')" :options="$selectionStatuses" />
            @endif

            @if ($reregistrationStatuses)
                <x-form.select name="reregistration_status" label="Status Daftar Ulang" placeholder="Semua status"
                               :value="request('reregistration_status')" :options="$reregistrationStatuses" />
            @endif

            @if ($decisionOptions)
                <x-form.select name="decision" label="Keputusan" placeholder="Semua"
                               :value="request('decision')" :options="$decisionOptions" />
            @endif

            {{ $slot }}

            <x-form.select name="per_page" label="Baris per halaman" :placeholder="null"
                           :value="request('per_page', 15)"
                           :options="[15 => '15', 25 => '25', 50 => '50', 100 => '100']" />
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn-primary">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan Filter
            </button>
        </div>
    </div>
</form>
