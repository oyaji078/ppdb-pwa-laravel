{{--
    One block of parent/guardian fields. Reused for father, mother and guardian
    so the three stay identical in layout and validation wiring.

    @param string $prefix  father|mother|guardian
    @param string $legend
    @param ?App\Models\ParentGuardian $model
--}}
@php
    use App\Http\Requests\Registration\ParentGuardianRequest;

    $educations = collect(config('ppdb.reference.educations'))->mapWithKeys(fn ($e) => [$e => $e])->all();
    $incomes = collect(config('ppdb.reference.income_ranges'))->mapWithKeys(fn ($i) => [$i => $i])->all();
    $isGuardian = $prefix === 'guardian';

    // Reflects what was last submitted, so a rejected form comes back with the
    // block in the state the family left it in.
    $isAlive = (bool) old($prefix.'.is_alive', $model?->is_alive ?? true);
@endphp

<fieldset class="rounded-lg border border-slate-200 p-5"
          @unless ($isGuardian) x-data="{ alive: {{ $isAlive ? 'true' : 'false' }} }" @endunless>
    <legend class="px-2 text-sm font-semibold text-slate-900">{{ $legend }}</legend>

    @if ($isGuardian)
        <p class="text-xs text-slate-500">Isi hanya bila calon peserta didik tinggal bersama wali. Kosongkan bila tidak ada.</p>
    @else
        {{-- Says outright that clearing the box releases the block. Without it
             the way past a required name is not discoverable. --}}
        <p class="text-xs text-slate-500" x-show="alive" x-cloak>
            Hanya nama yang wajib diisi. Isian lain boleh dikosongkan.
        </p>
        <p class="text-xs text-slate-500" x-show="! alive" x-cloak>
            Ditandai sudah meninggal, jadi seluruh isian di bagian ini boleh dikosongkan.
        </p>
        <noscript>
            <p class="text-xs text-slate-500">
                Hanya nama yang wajib diisi, dan itu pun boleh dikosongkan bila centang &quot;Masih hidup&quot; dilepas.
            </p>
        </noscript>
    @endif

    <div class="mt-4 grid gap-5 sm:grid-cols-2">
        {{-- Marked with `indicate` rather than `required`: the rule depends on
             the "masih hidup" box below, which the browser's own required check
             cannot express, and enforcing it here would leave someone whose
             parent has died unable to submit the form at all. --}}
        <x-form.input :name="$prefix.'.name'" :id="$prefix.'_name'" label="Nama Lengkap"
                      :value="$model?->name" :indicate="! $isGuardian && $isAlive" autocomplete="off"
                      maxlength="150" />

        <x-form.input :name="$prefix.'.nik'" :id="$prefix.'_nik'" label="NIK"
                      :value="$model?->nik" inputmode="numeric" pattern="[0-9]{16}"
                      minlength="16" maxlength="16" autocomplete="off"
                      hint="16 angka sesuai Kartu Keluarga." />

        {{-- Full date of birth, not just a year, entered with the native picker
             and bounded to an age a parent can plausibly be. --}}
        <x-form.input :name="$prefix.'.birth_date'" :id="$prefix.'_birth_date'" type="date" label="Tanggal Lahir"
                      :value="$model?->birth_date?->toDateString()"
                      :min="ParentGuardianRequest::oldestDate()"
                      :max="ParentGuardianRequest::youngestDate()" />

        <x-form.select :name="$prefix.'.education'" :id="$prefix.'_education'" label="Pendidikan Terakhir"
                       :value="$model?->education" :options="$educations" placeholder="Pilih pendidikan" />

        <x-form.input :name="$prefix.'.occupation'" :id="$prefix.'_occupation'" label="Pekerjaan"
                      :value="$model?->occupation" maxlength="100" autocomplete="off" />

        <x-form.select :name="$prefix.'.monthly_income'" :id="$prefix.'_monthly_income'" label="Penghasilan per Bulan"
                       :value="$model?->monthly_income" :options="$incomes" placeholder="Pilih rentang penghasilan" />

        {{-- Never `required` on its own: the rule is "at least one of father,
             mother or guardian", which no single field can enforce. The
             asterisk keeps the requirement visible on the blocks that can
             satisfy it, and the hint says what the rule actually is. --}}
        <x-form.input :name="$prefix.'.phone'" :id="$prefix.'_phone'" type="tel" label="Nomor HP"
                      :value="$model?->phone" inputmode="tel" autocomplete="off"
                      minlength="8" maxlength="25" placeholder="08xxxxxxxxxx"
                      :indicate="! $isGuardian && $isAlive"
                      hint="Cukup salah satu nomor dari ayah, ibu, atau wali." />

        @if ($isGuardian)
            <div class="sm:col-span-2">
                <x-form.textarea :name="$prefix.'.address'" :id="$prefix.'_address'" label="Alamat Wali"
                                 :value="$model?->address" rows="2" maxlength="500" />
            </div>
        @else
            <div class="flex items-end pb-1">
                <x-form.checkbox :name="$prefix.'.is_alive'" :id="$prefix.'_is_alive'"
                                 label="Masih hidup" :checked="$isAlive" x-model="alive"
                                 hint="Hapus centang bila sudah meninggal; isian di bagian ini lalu boleh dikosongkan." />
            </div>
        @endif
    </div>
</fieldset>
