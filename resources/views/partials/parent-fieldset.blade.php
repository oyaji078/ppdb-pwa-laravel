{{--
    One block of parent/guardian fields. Reused for father, mother and guardian
    so the three stay identical in layout and validation wiring.

    @param string $prefix  father|mother|guardian
    @param string $legend
    @param ?App\Models\ParentGuardian $model
    @param bool $required
--}}
@php
    $educations = collect(config('ppdb.reference.educations'))->mapWithKeys(fn ($e) => [$e => $e])->all();
    $incomes = collect(config('ppdb.reference.income_ranges'))->mapWithKeys(fn ($i) => [$i => $i])->all();
    $isGuardian = $prefix === 'guardian';
@endphp

<fieldset class="rounded-lg border border-slate-200 p-5">
    <legend class="px-2 text-sm font-semibold text-slate-900">{{ $legend }}</legend>

    @if ($isGuardian)
        <p class="text-xs text-slate-500">Isi hanya bila calon peserta didik tinggal bersama wali. Kosongkan bila tidak ada.</p>
    @endif

    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        <x-form.input :name="$prefix.'.name'" :id="$prefix.'_name'" label="Nama Lengkap"
                      :value="$model?->name" :required="$required" />

        <x-form.input :name="$prefix.'.nik'" :id="$prefix.'_nik'" label="NIK"
                      :value="$model?->nik" inputmode="numeric" maxlength="16" />

        <x-form.input :name="$prefix.'.birth_year'" :id="$prefix.'_birth_year'" type="number" label="Tahun Lahir"
                      :value="$model?->birth_year" min="1930" max="{{ now()->year }}" />

        <x-form.select :name="$prefix.'.education'" :id="$prefix.'_education'" label="Pendidikan Terakhir"
                       :value="$model?->education" :options="$educations" placeholder="Pilih pendidikan" />

        <x-form.input :name="$prefix.'.occupation'" :id="$prefix.'_occupation'" label="Pekerjaan"
                      :value="$model?->occupation" />

        <x-form.select :name="$prefix.'.monthly_income'" :id="$prefix.'_monthly_income'" label="Penghasilan per Bulan"
                       :value="$model?->monthly_income" :options="$incomes" placeholder="Pilih rentang penghasilan" />

        <x-form.input :name="$prefix.'.phone'" :id="$prefix.'_phone'" label="Nomor HP"
                      :value="$model?->phone" inputmode="tel" placeholder="08xxxxxxxxxx" />

        @if ($isGuardian)
            <div class="sm:col-span-2">
                <x-form.textarea :name="$prefix.'.address'" :id="$prefix.'_address'" label="Alamat Wali"
                                 :value="$model?->address" rows="2" />
            </div>
        @else
            <div class="flex items-end pb-1">
                <x-form.checkbox :name="$prefix.'.is_alive'" :id="$prefix.'_is_alive'"
                                 label="Masih hidup" :checked="$model?->is_alive ?? true" />
            </div>
        @endif
    </div>
</fieldset>
