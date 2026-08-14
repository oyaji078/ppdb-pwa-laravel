@extends('layouts.admin')

@section('title', 'Seleksi')
@section('subheading', 'Penetapan hasil seleksi untuk pendaftar yang sudah terverifikasi.')

@section('content')
    <div class="space-y-5">
        <x-alert type="info" title="Hasil ditetapkan dulu, dipublikasikan kemudian">
            Keputusan yang Anda simpan berstatus draft dan belum terlihat oleh pendaftar.
            Pendaftar baru dapat melihat hasilnya setelah Anda menekan Publikasikan.
        </x-alert>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Terverifikasi" :value="number_format($summary['verified'], 0, ',', '.')"
                         icon="file-check" tone="brand" />
            <x-stat-card label="Belum Diputuskan" :value="number_format($summary['undecided'], 0, ',', '.')"
                         icon="circle-dashed" tone="warning"
                         :href="route('admin.selection.index', array_merge(request()->query(), ['decision' => 'undecided']))" />
            <x-stat-card label="Draft Belum Publikasi" :value="number_format($summary['unpublished'], 0, ',', '.')"
                         icon="file-clock" tone="info"
                         :href="route('admin.selection.index', array_merge(request()->query(), ['decision' => 'unpublished']))" />
            <x-stat-card label="Sudah Dipublikasikan" :value="number_format($summary['published'], 0, ',', '.')"
                         icon="megaphone" tone="success"
                         :href="route('admin.selection.index', array_merge(request()->query(), ['decision' => 'published']))" />
        </section>

        <x-admin.filter-bar :action="route('admin.selection.index')"
                            :academic-years="$academicYears" :waves="$waves" :tracks="$tracks" :programs="$programs"
                            :decision-options="['undecided' => 'Belum diputuskan', 'unpublished' => 'Draft belum publikasi', 'published' => 'Sudah dipublikasikan']" />

        @if ($summary['unpublished'] > 0)
            <form method="POST" action="{{ route('admin.selection.publish-bulk') }}" class="card p-5"
                  onsubmit="return confirm('Publikasikan seluruh hasil seleksi pada filter ini? Pendaftar akan langsung melihat hasilnya.')">
                @csrf
                @foreach (request()->except(['confirm', 'page', '_token']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <h2 class="font-semibold text-slate-900">Publikasi Massal</h2>
                <p class="prose-content mt-1.5">
                    Mempublikasikan <strong>{{ number_format($summary['unpublished'], 0, ',', '.') }}</strong>
                    hasil seleksi yang masih berstatus draft pada filter saat ini. Tindakan ini tidak dapat dibatalkan.
                </p>

                <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <x-form.checkbox name="confirm" label="Saya memahami hasil akan langsung terlihat oleh pendaftar." />
                    <button type="submit" class="btn-primary shrink-0">
                        <x-icon name="megaphone" class="h-4 w-4" />
                        Publikasikan Semua
                    </button>
                </div>
            </form>
        @endif

        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3.5">
                <p class="text-sm text-slate-600">
                    <span class="font-semibold text-slate-900">{{ number_format($registrations->total(), 0, ',', '.') }}</span>
                    pendaftar terverifikasi
                </p>
            </div>

            @if ($registrations->isEmpty())
                <x-empty-state icon="clipboard-list" title="Belum ada pendaftar terverifikasi"
                               description="Seleksi hanya dapat dilakukan untuk pendaftar yang sudah lolos verifikasi berkas." />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($registrations as $registration)
                        @php $result = $registration->selectionResult; @endphp

                        <li class="p-5" x-data="{ open: {{ $result === null ? 'false' : 'false' }} }">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('admin.registrations.show', $registration) }}"
                                           class="font-mono text-sm font-semibold text-brand-700 hover:text-brand-800">
                                            {{ $registration->registration_number }}
                                        </a>
                                        @if ($result)
                                            <x-badge :class="$result->status->badge()">{{ $result->status->label() }}</x-badge>
                                            @if ($result->isPublished())
                                                <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200" icon="megaphone">
                                                    Dipublikasikan
                                                </x-badge>
                                            @else
                                                <x-badge class="bg-slate-100 text-slate-600 ring-slate-200" icon="file-clock">
                                                    Draft
                                                </x-badge>
                                            @endif
                                        @else
                                            <x-badge class="bg-amber-50 text-amber-800 ring-amber-200">Belum diputuskan</x-badge>
                                        @endif
                                    </div>

                                    <p class="mt-1 font-medium text-slate-900">{{ $registration->applicant->full_name }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $registration->admissionTrack->name }} &middot;
                                        {{ $registration->program?->name ?? 'Tanpa program' }} &middot;
                                        {{ $registration->applicant->previousSchool?->school_name ?? '-' }}
                                    </p>

                                    @if ($result && ($result->score !== null || $result->rank !== null))
                                        <p class="mt-1 text-xs text-slate-600">
                                            @if ($result->score !== null) Nilai {{ $result->score }} @endif
                                            @if ($result->rank !== null) &middot; Peringkat {{ $result->rank }} @endif
                                        </p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 flex-wrap gap-2">
                                    @if ($result && ! $result->isPublished())
                                        @can('publishResult', $registration)
                                            <form method="POST" action="{{ route('admin.selection.publish', $registration) }}"
                                                  onsubmit="return confirm('Publikasikan hasil seleksi {{ $registration->registration_number }}?')">
                                                @csrf
                                                <button type="submit" class="btn-primary btn-sm">
                                                    <x-icon name="megaphone" class="h-3.5 w-3.5" />
                                                    Publikasikan
                                                </button>
                                            </form>
                                        @endcan

                                        <form method="POST" action="{{ route('admin.selection.revoke', $registration) }}"
                                              onsubmit="return confirm('Batalkan draft hasil seleksi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-secondary btn-sm text-rose-600">
                                                <x-icon name="undo-2" class="h-3.5 w-3.5" />
                                                Batalkan
                                            </button>
                                        </form>
                                    @endif

                                    @unless ($result?->isPublished())
                                        <button type="button" @click="open = ! open" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            {{ $result ? 'Ubah' : 'Tetapkan' }}
                                        </button>
                                    @endunless
                                </div>
                            </div>

                            @unless ($result?->isPublished())
                                <form method="POST" action="{{ route('admin.selection.store', $registration) }}"
                                      x-show="open" x-cloak x-collapse class="mt-4 rounded-lg bg-slate-50 p-4">
                                    @csrf

                                    <div class="grid gap-4 sm:grid-cols-4">
                                        <x-form.select :name="'status'" :id="'status-'.$registration->id" label="Hasil" required
                                                       :value="$result?->status->value" :options="$statusOptions"
                                                       placeholder="Pilih hasil" />

                                        <x-form.input :name="'score'" :id="'score-'.$registration->id" type="number"
                                                      label="Nilai" step="0.01" min="0" :value="$result?->score" />

                                        <x-form.input :name="'rank'" :id="'rank-'.$registration->id" type="number"
                                                      label="Peringkat" min="1" :value="$result?->rank" />

                                        <div class="sm:col-span-4">
                                            <x-form.textarea :name="'note'" :id="'note-'.$registration->id"
                                                             label="Catatan (opsional)" rows="2" :value="$result?->note"
                                                             hint="Catatan ini akan terlihat pendaftar setelah hasil dipublikasikan." />
                                        </div>
                                    </div>

                                    <button type="submit" class="btn-primary btn-sm mt-4">
                                        <x-icon name="save" class="h-3.5 w-3.5" />
                                        Simpan sebagai Draft
                                    </button>
                                </form>
                            @endunless
                        </li>
                    @endforeach
                </ul>

                <div class="border-t border-slate-100 px-5 py-4">{{ $registrations->links() }}</div>
            @endif
        </div>
    </div>
@endsection
