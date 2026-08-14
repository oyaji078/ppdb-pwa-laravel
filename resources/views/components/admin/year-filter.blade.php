@props(['action', 'academicYears', 'selected' => null])

{{-- Compact single-select filter used by the configuration screens. --}}
<form method="GET" action="{{ $action }}" class="card flex flex-wrap items-end gap-3 p-4">
    <div class="min-w-56 flex-1">
        <x-form.select name="academic_year_id" label="Tahun Ajaran" placeholder="Semua tahun ajaran"
                       :value="$selected" :options="$academicYears->pluck('name', 'id')->all()" />
    </div>

    <button type="submit" class="btn-secondary">
        <x-icon name="filter" class="h-4 w-4" />
        Terapkan
    </button>

    {{ $slot }}
</form>
