@extends('layouts.applicant')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        {{-- Welcome card --}}
        <section class="card overflow-hidden">
            <div class="bg-gradient-to-r from-brand-700 to-brand-600 px-6 py-6 text-white sm:px-8">
                <h1 class="text-xl font-bold sm:text-2xl">
                    Selamat Datang, {{ Str::before($applicant->full_name, ' ') ?: $applicant->full_name }}
                </h1>
                <p class="mt-1 text-sm text-brand-100">Berikut informasi pendaftaran Anda.</p>

                <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-white/10 p-3.5 ring-1 ring-white/20 ring-inset">
                        <dt class="text-xs text-brand-100">Status Pendaftaran</dt>
                        <dd class="mt-1 text-sm font-bold">{{ $registration->registration_status->label() }}</dd>
                    </div>
                    <div class="rounded-lg bg-white/10 p-3.5 ring-1 ring-white/20 ring-inset">
                        <dt class="text-xs text-brand-100">Tahun Ajaran</dt>
                        <dd class="mt-1 text-sm font-bold">{{ $registration->academicYear->name }}</dd>
                    </div>
                    <div class="rounded-lg bg-white/10 p-3.5 ring-1 ring-white/20 ring-inset">
                        <dt class="text-xs text-brand-100">Gelombang</dt>
                        <dd class="mt-1 text-sm font-bold">{{ $registration->wave->name }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        {{-- Result banner --}}
        @if ($registration->hasPublishedResult())
            @php $result = $registration->selectionResult; @endphp

            <x-alert :type="$registration->isAccepted() ? 'success' : ($registration->selection_status->value === 'reserve' ? 'warning' : 'error')"
                     :title="'Hasil Seleksi: '.$registration->selection_status->label()">
                @if ($result->note)
                    <p>{{ $result->note }}</p>
                @endif
                @if ($registration->isAccepted())
                    <p class="mt-1">
                        Status daftar ulang Anda: <strong>{{ $registration->reregistration_status->label() }}</strong>.
                        Silakan mengikuti jadwal daftar ulang yang diumumkan panitia.
                    </p>
                @endif
            </x-alert>
        @elseif ($registration->isVerified())
            <x-alert type="info" title="Menunggu pengumuman hasil seleksi">
                Berkas Anda sudah terverifikasi. Hasil seleksi belum diumumkan.
            </x-alert>
        @endif

        {{-- Revision warning --}}
        @php
            use App\Enums\DocumentStatus;

            $needsRevision = $registration->documents->whereIn('verification_status', [
                DocumentStatus::RevisionRequired,
                DocumentStatus::Rejected,
            ]);
        @endphp

        @if ($needsRevision->isNotEmpty())
            <x-alert type="warning" title="Ada berkas yang perlu diperbaiki">
                <ul class="mt-1 list-inside list-disc space-y-0.5">
                    @foreach ($needsRevision as $document)
                        <li>{{ $document->documentType->name }} &mdash; {{ $document->verification_note }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('applicant.documents.index') }}" class="btn-secondary btn-sm mt-3">
                    <x-icon name="upload" class="h-3.5 w-3.5" />
                    Perbaiki Berkas
                </a>
            </x-alert>
        @endif

        {{-- Summary cards --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Biodata" :value="$biodataProgress['percent'].'%'"
                         icon="user-round"
                         :tone="$biodataProgress['percent'] === 100 ? 'success' : 'warning'"
                         :progress="$biodataProgress['percent']"
                         :caption="$biodataProgress['percent'] === 100 ? 'Lengkap' : 'Belum lengkap'" />

            <x-stat-card label="Berkas"
                         :value="$documentProgress['uploaded'].' / '.$documentProgress['total']"
                         icon="folder-open"
                         :tone="$documentProgress['percent'] === 100 ? 'success' : 'warning'"
                         :progress="$documentProgress['percent']"
                         :caption="$documentProgress['percent'].'% berkas wajib terunggah'"
                         :href="route('applicant.documents.index')" />

            <x-stat-card label="Program Pilihan"
                         :value="$registration->program?->code ? Str::upper($registration->program->code) : '-'"
                         icon="book-open" tone="info"
                         :caption="$registration->program?->name ?? 'Belum memilih program'"
                         :href="route('applicant.program')" />

            <x-stat-card label="Tanggal Daftar"
                         :value="$registration->submitted_at?->translatedFormat('d M Y') ?? '-'"
                         icon="calendar-check" tone="brand"
                         :caption="$registration->submitted_at?->translatedFormat('H:i').' WITA'" />
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Progress pendaftaran --}}
            <section class="card p-6 lg:col-span-2">
                <h2 class="font-bold text-slate-900">Progress Pendaftaran</h2>

                <ol class="mt-5 space-y-4">
                    @foreach ($timeline as $index => $stage)
                        <li class="flex gap-3.5">
                            <div class="flex flex-col items-center">
                                <span @class([
                                    'flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                    'bg-emerald-500 text-white' => $stage['state'] === 'done',
                                    'bg-brand-600 text-white' => $stage['state'] === 'current',
                                    'bg-amber-500 text-white' => $stage['state'] === 'attention',
                                    'bg-slate-100 text-slate-400' => $stage['state'] === 'upcoming',
                                ])>
                                    @if ($stage['state'] === 'done')
                                        <x-icon name="check" class="h-4 w-4" />
                                    @elseif ($stage['state'] === 'attention')
                                        <x-icon name="triangle-alert" class="h-4 w-4" />
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>

                                @unless ($loop->last)
                                    <span @class([
                                        'mt-1 w-0.5 flex-1',
                                        'bg-emerald-200' => $stage['state'] === 'done',
                                        'bg-slate-200' => $stage['state'] !== 'done',
                                    ])></span>
                                @endunless
                            </div>

                            <div class="min-w-0 flex-1 pb-1">
                                <p @class([
                                    'text-sm font-semibold',
                                    'text-slate-900' => $stage['state'] !== 'upcoming',
                                    'text-slate-400' => $stage['state'] === 'upcoming',
                                ])>{{ $stage['label'] }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $stage['description'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Pengumuman + kontak --}}
            <div class="space-y-6">
                <section class="card p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-bold text-slate-900">Pengumuman Terbaru</h2>
                        <a href="{{ route('applicant.announcements.index') }}" class="shrink-0 text-xs font-semibold text-brand-600 hover:text-brand-700">
                            Semua
                        </a>
                    </div>

                    @if ($announcements->isEmpty())
                        <x-empty-state icon="megaphone" title="Belum ada pengumuman" class="py-8" />
                    @else
                        <ul class="mt-4 space-y-3">
                            @foreach ($announcements as $announcement)
                                <li>
                                    <a href="{{ route('applicant.announcements.show', $announcement) }}"
                                       class="block rounded-lg p-3 ring-1 ring-slate-200 transition-colors hover:bg-slate-50">
                                        <p class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d M Y') }}</p>
                                        <p class="mt-0.5 text-sm font-medium text-slate-900">{{ $announcement->title }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="card p-6">
                    <h2 class="font-bold text-slate-900">Kontak Panitia</h2>
                    <p class="prose-content mt-1.5">Hubungi panitia bila ada kendala pada pendaftaran Anda.</p>

                    <ul class="mt-4 space-y-2.5 text-sm">
                        @if ($settings->get('contact_person'))
                            <li class="flex items-center gap-2.5 text-slate-700">
                                <x-icon name="user" class="h-4 w-4 shrink-0 text-slate-400" />
                                {{ $settings->get('contact_person') }}
                            </li>
                        @endif
                        @if ($settings->get('contact_email'))
                            <li class="flex items-center gap-2.5">
                                <x-icon name="mail" class="h-4 w-4 shrink-0 text-slate-400" />
                                <a href="mailto:{{ $settings->get('contact_email') }}" class="break-all text-brand-700 hover:text-brand-800">
                                    {{ $settings->get('contact_email') }}
                                </a>
                            </li>
                        @endif
                        @if ($settings->whatsappUrl())
                            <li class="flex items-center gap-2.5">
                                <x-icon name="message-circle" class="h-4 w-4 shrink-0 text-slate-400" />
                                <a href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener" class="text-brand-700 hover:text-brand-800">
                                    {{ $settings->get('contact_whatsapp') }}
                                </a>
                            </li>
                        @endif
                        @if ($settings->get('school_phone'))
                            <li class="flex items-center gap-2.5 text-slate-700">
                                <x-icon name="phone" class="h-4 w-4 shrink-0 text-slate-400" />
                                {{ $settings->get('school_phone') }}
                            </li>
                        @endif
                    </ul>
                </section>
            </div>
        </div>

        {{-- Berkas yang diunggah --}}
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-6 py-4">
                <h2 class="font-bold text-slate-900">Berkas yang Diunggah</h2>
                <a href="{{ route('applicant.documents.index') }}" class="btn-secondary btn-sm shrink-0">
                    <x-icon name="folder-open" class="h-3.5 w-3.5" />
                    Kelola Berkas
                </a>
            </div>

            @php $uploaded = $registration->documents->keyBy('document_type_id'); @endphp

            @if ($documentTypes->isEmpty())
                <x-empty-state icon="file-x" title="Belum ada persyaratan berkas" />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($documentTypes as $type)
                        @php $file = $uploaded[$type->id] ?? null; @endphp

                        <li class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
                            <div class="flex min-w-0 items-center gap-3">
                                <span @class([
                                    'flex h-9 w-9 shrink-0 items-center justify-center rounded-lg',
                                    'bg-emerald-50 text-emerald-600' => $file?->verification_status->value === 'verified',
                                    'bg-amber-50 text-amber-600' => in_array($file?->verification_status->value, ['revision_required', 'rejected'], true),
                                    'bg-blue-50 text-blue-600' => $file?->verification_status->value === 'pending',
                                    'bg-slate-100 text-slate-400' => $file === null,
                                ])>
                                    <x-icon :name="$file ? ($file->isPdf() ? 'file-text' : 'image') : 'file-plus'" class="h-4.5 w-4.5" />
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $type->name }}</p>
                                    <p class="truncate text-xs text-slate-500">
                                        {{ $file ? $file->original_name.' · '.$file->humanFileSize() : 'Belum diunggah' }}
                                    </p>
                                </div>
                            </div>

                            @if ($file)
                                <x-badge :class="$file->verification_status->badge()">{{ $file->verification_status->label() }}</x-badge>
                            @elseif ($type->pivot->is_required)
                                <x-badge class="bg-rose-50 text-rose-700 ring-rose-200">Wajib</x-badge>
                            @else
                                <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Opsional</x-badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
