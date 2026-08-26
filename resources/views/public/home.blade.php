@extends('layouts.public')

@section('title', 'Beranda')

@section('hero')
    <section class="relative overflow-hidden bg-brand-700 text-white">
        @if ($settings->heroBannerUrl())
            {{-- Uploaded banner, with a scrim dark enough to keep the text
                 readable whatever photo the school chose. --}}
            <img src="{{ $settings->heroBannerUrl() }}" alt=""
                 class="absolute inset-0 h-full w-full object-cover" aria-hidden="true">
            <div class="absolute inset-0 bg-brand-950"
                 style="opacity: {{ $settings->heroOverlayOpacity() }}" aria-hidden="true"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-brand-700 via-brand-700 to-brand-900" aria-hidden="true"></div>
        @endif

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    @if ($academicYear)
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold ring-1 ring-white/25 ring-inset">
                            <x-icon name="calendar-days" class="h-3.5 w-3.5" />
                            {{ $settings->admissionName() }} Tahun Ajaran {{ $academicYear->name }}
                        </span>
                    @endif

                    <h1 class="mt-5 text-3xl leading-tight font-bold sm:text-4xl lg:text-5xl">
                        {{ $settings->get('hero_title', 'Bergabung Bersama Kami') }}
                    </h1>

                    <p class="mt-4 max-w-xl text-base text-brand-100 sm:text-lg">
                        {{ $settings->get('hero_subtitle') }}
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @if ($academicYear?->registration_open && $openWaves->isNotEmpty())
                            <a href="{{ route('registration.start') }}"
                               class="btn inline-flex bg-white px-6 py-3 text-base text-brand-700 hover:bg-brand-50">
                                <x-icon name="user-plus" class="h-5 w-5" />
                                Daftar Sekarang
                            </a>
                        @else
                            <span class="btn inline-flex cursor-not-allowed bg-white/20 px-6 py-3 text-base text-white ring-1 ring-white/30 ring-inset">
                                <x-icon name="lock" class="h-5 w-5" />
                                Pendaftaran Belum Dibuka
                            </span>
                        @endif

                        <a href="{{ route('login') }}"
                           class="btn inline-flex bg-brand-600/40 px-6 py-3 text-base text-white ring-1 ring-white/30 ring-inset hover:bg-brand-600/60">
                            <x-icon name="search" class="h-5 w-5" />
                            Cek Status
                        </a>
                    </div>
                </div>

                <div class="space-y-4">
                    <x-applicant-login-box />

                    {{-- Stats compact under the login card so the hero keeps both. --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div class="rounded-lg bg-white/10 p-3 text-center ring-1 ring-white/20 ring-inset backdrop-blur">
                            <p class="text-xl font-bold">{{ number_format($registeredCount, 0, ',', '.') }}</p>
                            <p class="mt-0.5 text-xs text-brand-100">Pendaftar</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3 text-center ring-1 ring-white/20 ring-inset backdrop-blur">
                            <p class="text-xl font-bold">{{ $tracks->count() }}</p>
                            <p class="mt-0.5 text-xs text-brand-100">Jalur</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3 text-center ring-1 ring-white/20 ring-inset backdrop-blur">
                            <p class="text-xl font-bold">{{ $openWaves->count() }}</p>
                            <p class="mt-0.5 text-xs text-brand-100">Gelombang</p>
                        </div>
                        <div class="rounded-lg bg-white/10 p-3 text-center ring-1 ring-white/20 ring-inset backdrop-blur">
                            <p class="text-xl font-bold">{{ $facilities->count() }}</p>
                            <p class="mt-0.5 text-xs text-brand-100">Fasilitas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('content')
    <div class="mx-auto max-w-7xl space-y-16 px-4 pb-4 sm:px-6 lg:px-8">

        {{-- Informasi PPDB --}}
        <section aria-labelledby="informasi-ppdb">
            <h2 id="informasi-ppdb" class="text-xl font-bold text-slate-900 sm:text-2xl">
                Informasi {{ $settings->admissionName() }}
            </h2>

            @if ($academicYear)
                <p class="mt-1 text-sm text-slate-600">
                    Tahun Ajaran {{ $academicYear->name }} &mdash;
                    {{ $academicYear->registration_open ? 'pendaftaran sedang dibuka.' : 'pendaftaran belum dibuka.' }}
                </p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($openWaves as $wave)
                        <article class="card p-5">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="font-semibold text-slate-900">{{ $wave->name }}</h3>
                                <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200" icon="circle-check">Dibuka</x-badge>
                            </div>
                            <p class="mt-3 flex items-center gap-2 text-sm text-slate-600">
                                <x-icon name="calendar" class="h-4 w-4 text-slate-400" />
                                {{ $wave->start_at->translatedFormat('d M Y') }} &ndash; {{ $wave->end_at->translatedFormat('d M Y') }}
                            </p>
                            @if ($wave->quota)
                                <p class="mt-1.5 flex items-center gap-2 text-sm text-slate-600">
                                    <x-icon name="users" class="h-4 w-4 text-slate-400" />
                                    Kuota {{ number_format($wave->quota, 0, ',', '.') }} peserta
                                </p>
                            @endif
                        </article>
                    @empty
                        <div class="card sm:col-span-2 lg:col-span-3">
                            <x-empty-state icon="calendar-off" title="Belum ada gelombang yang dibuka"
                                           description="Pantau halaman ini secara berkala untuk mengetahui jadwal pembukaan pendaftaran." />
                        </div>
                    @endforelse
                </div>
            @else
                <div class="card mt-6">
                    <x-empty-state icon="calendar-off" title="Tahun ajaran belum ditetapkan"
                                   description="Panitia belum mengaktifkan tahun ajaran penerimaan." />
                </div>
            @endif
        </section>

        {{-- Jalur pendaftaran --}}
        @if ($tracks->isNotEmpty())
            <section aria-labelledby="jalur">
                <h2 id="jalur" class="text-xl font-bold text-slate-900 sm:text-2xl">Jalur Pendaftaran</h2>
                <p class="mt-1 text-sm text-slate-600">Pilih jalur yang sesuai dengan kondisi dan prestasi Ananda.</p>

                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tracks as $track)
                        <article class="card flex flex-col p-5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon name="route" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900">{{ $track->name }}</h3>
                            <p class="prose-content mt-2 flex-1">{{ $track->description }}</p>
                            @if ($track->quota)
                                <p class="mt-4 text-xs font-medium text-slate-500">Kuota {{ number_format($track->quota, 0, ',', '.') }} peserta</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Jadwal --}}
        @if ($schedules->isNotEmpty())
            <section aria-labelledby="jadwal">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 id="jadwal" class="text-xl font-bold text-slate-900 sm:text-2xl">Jadwal {{ $settings->admissionName() }}</h2>
                        <p class="mt-1 text-sm text-slate-600">Tahapan penerimaan peserta didik baru.</p>
                    </div>
                    <a href="{{ route('ppdb.schedule') }}" class="hidden shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700 sm:block">
                        Lihat semua
                    </a>
                </div>

                <ol class="mt-6 space-y-3">
                    @foreach ($schedules as $schedule)
                        <li class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-sm font-bold text-brand-700">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900">{{ $schedule->title }}</p>
                                    <p class="mt-0.5 text-sm text-slate-500">{{ $schedule->dateRange() }}</p>
                                </div>
                            </div>
                            <x-badge :class="$schedule->phaseBadge()">{{ $schedule->phaseLabel() }}</x-badge>
                        </li>
                    @endforeach
                </ol>

                <a href="{{ route('ppdb.schedule') }}" class="mt-4 inline-block text-sm font-semibold text-brand-600 sm:hidden">Lihat semua jadwal</a>
            </section>
        @endif

        {{-- Program sekolah --}}
        @if ($schoolPrograms->isNotEmpty())
            <section aria-labelledby="program">
                <h2 id="program" class="text-xl font-bold text-slate-900 sm:text-2xl">Program Sekolah</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($schoolPrograms as $program)
                        <article class="card p-5">
                            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <x-icon :name="$program->icon ?: 'book-open'" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 font-semibold text-slate-900">{{ $program->name }}</h3>
                            <p class="prose-content mt-2">{{ $program->excerpt }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Fasilitas --}}
        @if ($facilities->isNotEmpty())
            <section aria-labelledby="fasilitas">
                <h2 id="fasilitas" class="text-xl font-bold text-slate-900 sm:text-2xl">Fasilitas</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($facilities as $facility)
                        <article class="card overflow-hidden">
                            @if ($facility->image_path)
                                <img src="{{ Storage::disk('public')->url($facility->image_path) }}" alt="{{ $facility->name }}"
                                     class="h-40 w-full object-cover" loading="lazy">
                            @endif
                            <div class="p-5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                        <x-icon :name="$facility->icon ?: 'building-2'" class="h-4.5 w-4.5" />
                                    </span>
                                    <h3 class="font-semibold text-slate-900">{{ $facility->name }}</h3>
                                </div>
                                <p class="prose-content mt-3">{{ $facility->description }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Pengumuman & Berita --}}
        <section class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Berita Terbaru</h2>
                    <a href="{{ route('news.index') }}" class="shrink-0 text-sm font-semibold text-brand-600 hover:text-brand-700">Semua berita</a>
                </div>

                @if ($latestNews->isNotEmpty())
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach ($latestNews as $item)
                            <article class="card overflow-hidden">
                                @if ($item->cover_path)
                                    <img src="{{ Storage::disk('public')->url($item->cover_path) }}" alt=""
                                         class="h-40 w-full object-cover" loading="lazy">
                                @endif
                                <div class="p-5">
                                    <p class="text-xs text-slate-500">{{ $item->published_at?->translatedFormat('d F Y') }}</p>
                                    <h3 class="mt-1.5 font-semibold text-slate-900">
                                        <a href="{{ route('news.show', $item) }}" class="hover:text-brand-700">{{ $item->title }}</a>
                                    </h3>
                                    <p class="prose-content mt-2">{{ Str::limit($item->excerpt, 110) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="card mt-6">
                        <x-empty-state icon="newspaper" title="Belum ada berita" description="Berita sekolah akan tampil di sini." />
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Pengumuman</h2>

                @if ($announcements->isNotEmpty())
                    <ul class="mt-6 space-y-3">
                        @foreach ($announcements as $announcement)
                            <li class="card p-4">
                                <p class="text-xs text-slate-500">{{ $announcement->published_at?->translatedFormat('d F Y') }}</p>
                                <h3 class="mt-1 text-sm font-semibold text-slate-900">
                                    <a href="{{ route('announcements.show', $announcement) }}" class="hover:text-brand-700">
                                        {{ $announcement->title }}
                                    </a>
                                </h3>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('announcements.index') }}" class="mt-4 inline-block text-sm font-semibold text-brand-600">Semua pengumuman</a>
                @else
                    <div class="card mt-6">
                        <x-empty-state icon="megaphone" title="Belum ada pengumuman" />
                    </div>
                @endif
            </div>
        </section>

        {{-- FAQ --}}
        @if ($faq !== [])
            <section aria-labelledby="faq">
                <h2 id="faq" class="text-xl font-bold text-slate-900 sm:text-2xl">Pertanyaan yang Sering Diajukan</h2>

                <div class="mt-6 space-y-3">
                    @foreach ($faq as $item)
                        <details class="card group p-0">
                            <summary class="flex cursor-pointer items-center justify-between gap-3 p-4 text-sm font-semibold text-slate-900">
                                {{ $item['question'] }}
                                <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-slate-400 transition-transform group-open:rotate-180" />
                            </summary>
                            <p class="prose-content border-t border-slate-100 p-4">{{ $item['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Kontak panitia --}}
        <section class="card bg-brand-600 p-8 text-white ring-brand-600">
            <div class="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-center">
                <div>
                    <h2 class="text-xl font-bold sm:text-2xl">Butuh bantuan?</h2>
                    <p class="mt-2 max-w-xl text-sm text-brand-100">
                        Hubungi panitia {{ $settings->admissionName() }} untuk pertanyaan seputar pendaftaran, berkas, maupun jadwal.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    @if ($settings->whatsappUrl())
                        <a href="{{ $settings->whatsappUrl() }}" target="_blank" rel="noopener"
                           class="btn bg-white text-brand-700 hover:bg-brand-50">
                            <x-icon name="message-circle" class="h-4 w-4" />
                            WhatsApp Panitia
                        </a>
                    @endif
                    <a href="{{ route('contact') }}" class="btn bg-brand-500/50 text-white ring-1 ring-white/30 ring-inset hover:bg-brand-500/70">
                        <x-icon name="mail" class="h-4 w-4" />
                        Halaman Kontak
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
