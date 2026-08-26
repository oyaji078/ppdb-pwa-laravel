<footer class="mt-16 bg-slate-900 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-3">
                    @if ($settings->logoUrl())
                        <img src="{{ $settings->logoUrl() }}" alt="" class="h-11 w-11 rounded-lg bg-white object-contain p-1">
                    @else
                        <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-600 text-white">
                            <x-icon name="graduation-cap" class="h-6 w-6" />
                        </span>
                    @endif
                    <div>
                        <p class="font-bold text-white">{{ $settings->schoolName() }}</p>
                        <p class="text-sm text-slate-400">{{ $settings->get('admission_tagline') }}</p>
                    </div>
                </div>

                @if ($settings->get('school_address'))
                    <p class="mt-5 flex items-start gap-2 text-sm">
                        <x-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-slate-500" />
                        <span>{{ $settings->get('school_address') }}</span>
                    </p>
                @endif
            </div>

            <div>
                <h2 class="text-sm font-semibold tracking-wide text-white uppercase">Tautan</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ route('profile') }}" class="hover:text-white">Profil Sekolah</a></li>
                    <li><a href="{{ route('ppdb.index') }}" class="hover:text-white">Informasi {{ $settings->admissionName() }}</a></li>
                    <li><a href="{{ route('ppdb.schedule') }}" class="hover:text-white">Jadwal</a></li>
                    <li><a href="{{ route('ppdb.requirements') }}" class="hover:text-white">Persyaratan</a></li>
                    <li><a href="{{ route('downloads.index') }}" class="hover:text-white">Unduhan</a></li>
                    <li><a href="{{ route('login') }}" class="hover:text-white">Masuk / Cek Status</a></li>
                </ul>
            </div>

            <div>
                <h2 class="text-sm font-semibold tracking-wide text-white uppercase">Kontak Panitia</h2>
                <ul class="mt-4 space-y-2 text-sm">
                    @if ($settings->get('contact_person'))
                        <li class="flex items-center gap-2">
                            <x-icon name="user" class="h-4 w-4 text-slate-500" />
                            {{ $settings->get('contact_person') }}
                        </li>
                    @endif
                    @if ($settings->get('contact_email'))
                        <li class="flex items-center gap-2">
                            <x-icon name="mail" class="h-4 w-4 text-slate-500" />
                            <a href="mailto:{{ $settings->get('contact_email') }}" class="break-all hover:text-white">{{ $settings->get('contact_email') }}</a>
                        </li>
                    @endif
                    @if ($settings->whatsappUrl())
                        <li class="flex items-center gap-2">
                            <x-icon name="message-circle" class="h-4 w-4 text-slate-500" />
                            <a href="{{ $settings->whatsappUrl() }}" rel="noopener" target="_blank" class="hover:text-white">
                                {{ $settings->get('contact_whatsapp') }}
                            </a>
                        </li>
                    @endif
                    @if ($settings->get('school_phone'))
                        <li class="flex items-center gap-2">
                            <x-icon name="phone" class="h-4 w-4 text-slate-500" />
                            {{ $settings->get('school_phone') }}
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-10 flex flex-col items-center justify-between gap-3 border-t border-slate-800 pt-6 text-xs text-slate-500 sm:flex-row">
            <p>&copy; {{ now()->year }} {{ $settings->schoolName() }}. Seluruh hak cipta dilindungi.</p>
            {{-- One sign-in serves everyone now, so this is no longer staff-only. --}}
            <a href="{{ route('login') }}" class="hover:text-slate-300">Masuk</a>
        </div>
    </div>
</footer>
