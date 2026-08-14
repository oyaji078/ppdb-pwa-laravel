@extends('layouts.public')

@section('title', 'Informasi '.$settings->admissionName())

@section('hero')
    <x-page-header :title="'Informasi '.$settings->admissionName()"
                   :subtitle="$academicYear ? 'Tahun Ajaran '.$academicYear->name : 'Tahun ajaran belum ditetapkan panitia.'"
                   :breadcrumb="[$settings->admissionName() => null]">
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('ppdb.schedule') }}" class="btn-secondary btn-sm">
                <x-icon name="calendar-clock" class="h-4 w-4" />
                Jadwal
            </a>
            <a href="{{ route('ppdb.requirements') }}" class="btn-secondary btn-sm">
                <x-icon name="file-stack" class="h-4 w-4" />
                Persyaratan
            </a>
            @if ($academicYear?->registration_open)
                <a href="{{ route('registration.start') }}" class="btn-primary btn-sm">
                    <x-icon name="user-plus" class="h-4 w-4" />
                    Daftar Sekarang
                </a>
            @endif
        </div>
    </x-page-header>
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
        @unless ($academicYear)
            <div class="card">
                <x-empty-state icon="calendar-off" title="Penerimaan belum dibuka"
                               description="Panitia belum menetapkan tahun ajaran aktif. Silakan periksa kembali nanti." />
            </div>
        @else
            @unless ($academicYear->registration_open)
                <x-alert type="warning" title="Pendaftaran sedang ditutup">
                    Formulir pendaftaran belum dapat diisi. Informasi jadwal pembukaan dapat dilihat pada halaman jadwal.
                </x-alert>
            @endunless

            {{-- Gelombang --}}
            <section aria-labelledby="gelombang">
                <h2 id="gelombang" class="text-xl font-bold text-slate-900">Gelombang Pendaftaran</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($waves as $wave)
                        <article class="card p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ $wave->name }}</h3>
                                    <p class="mt-0.5 text-xs text-slate-500">Kode gelombang {{ $wave->code }}</p>
                                </div>
                                @if ($wave->isOpen())
                                    <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Dibuka</x-badge>
                                @elseif ($wave->end_at->isPast())
                                    <x-badge class="bg-slate-100 text-slate-600 ring-slate-200">Ditutup</x-badge>
                                @else
                                    <x-badge class="bg-blue-50 text-blue-700 ring-blue-200">Akan Datang</x-badge>
                                @endif
                            </div>

                            <dl class="mt-4 space-y-1.5 text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Mulai</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $wave->start_at->translatedFormat('d M Y') }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-slate-500">Selesai</dt>
                                    <dd class="text-right font-medium text-slate-800">{{ $wave->end_at->translatedFormat('d M Y') }}</dd>
                                </div>
                                @if ($wave->quota)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Kuota</dt>
                                        <dd class="text-right font-medium text-slate-800">{{ number_format($wave->quota, 0, ',', '.') }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </article>
                    @empty
                        <div class="card sm:col-span-2 lg:col-span-3">
                            <x-empty-state icon="layers" title="Belum ada gelombang" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Jalur --}}
            <section aria-labelledby="jalur-pendaftaran">
                <h2 id="jalur-pendaftaran" class="text-xl font-bold text-slate-900">Jalur Pendaftaran</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($tracks as $track)
                        <article class="card p-5">
                            <h3 class="font-semibold text-slate-900">{{ $track->name }}</h3>
                            <p class="prose-content mt-2">{{ $track->description }}</p>
                            @if ($track->quota)
                                <p class="mt-3 text-xs font-medium text-slate-500">Kuota {{ number_format($track->quota, 0, ',', '.') }} peserta</p>
                            @endif
                        </article>
                    @empty
                        <div class="card sm:col-span-2 lg:col-span-3">
                            <x-empty-state icon="route" title="Belum ada jalur pendaftaran" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Program --}}
            <section aria-labelledby="program-peminatan">
                <h2 id="program-peminatan" class="text-xl font-bold text-slate-900">Program / Peminatan</h2>

                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($programs as $program)
                        <article class="card p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="font-semibold text-slate-900">{{ $program->name }}</h3>
                                <x-badge class="bg-brand-50 text-brand-700 ring-brand-200">{{ Str::upper($program->code) }}</x-badge>
                            </div>
                            <p class="prose-content mt-2">{{ $program->description }}</p>
                            @if ($program->quota)
                                <p class="mt-3 text-xs font-medium text-slate-500">Daya tampung {{ number_format($program->quota, 0, ',', '.') }} peserta</p>
                            @endif
                        </article>
                    @empty
                        <div class="card sm:col-span-2 lg:col-span-3">
                            <x-empty-state icon="book-open" title="Belum ada program" />
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Jadwal ringkas --}}
            @if ($schedules->isNotEmpty())
                <section aria-labelledby="ringkasan-jadwal">
                    <div class="flex items-end justify-between gap-4">
                        <h2 id="ringkasan-jadwal" class="text-xl font-bold text-slate-900">Tahapan {{ $settings->admissionName() }}</h2>
                        <a href="{{ route('ppdb.schedule') }}" class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">Detail jadwal</a>
                    </div>

                    <ol class="mt-5 space-y-3">
                        @foreach ($schedules as $schedule)
                            <li class="card flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $schedule->title }}</p>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $schedule->dateRange() }}</p>
                                </div>
                                <x-badge :class="$schedule->phaseBadge()">{{ $schedule->phaseLabel() }}</x-badge>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            {{-- Pengumuman --}}
            @if ($announcements->isNotEmpty())
                <section aria-labelledby="pengumuman-ppdb">
                    <h2 id="pengumuman-ppdb" class="text-xl font-bold text-slate-900">Pengumuman Terbaru</h2>

                    <ul class="mt-5 space-y-3">
                        @foreach ($announcements as $announcement)
                            <li class="card p-5">
                                <p class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y') }}</p>
                                <h3 class="mt-1 font-semibold text-slate-900">
                                    <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-brand-700">
                                        {{ $announcement->title }}
                                    </a>
                                </h3>
                                <p class="prose-content mt-1.5">{{ Str::limit(strip_tags($announcement->content), 150) }}</p>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endunless
    </div>
@endsection
