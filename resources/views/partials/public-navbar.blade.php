@php
    $navigation = [
        ['label' => 'Beranda', 'route' => 'home', 'active' => 'home'],
        ['label' => 'Profil', 'route' => 'profile', 'active' => 'profile'],
        ['label' => 'Program', 'route' => 'programs', 'active' => 'programs'],
        ['label' => 'Fasilitas', 'route' => 'facilities', 'active' => 'facilities'],
        ['label' => 'Berita', 'route' => 'news.index', 'active' => 'news.*'],
        ['label' => 'Galeri', 'route' => 'gallery.index', 'active' => 'gallery.*'],
        ['label' => $settings->admissionName(), 'route' => 'ppdb.index', 'active' => 'ppdb.*'],
        ['label' => 'Unduhan', 'route' => 'downloads.index', 'active' => 'downloads.*'],
        ['label' => 'Kontak', 'route' => 'contact', 'active' => 'contact'],
    ];
@endphp

<header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Navigasi utama">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                @if ($settings->logoUrl())
                    <img src="{{ $settings->logoUrl() }}" alt="Logo {{ $settings->schoolName() }}" class="h-10 w-10 rounded-lg object-contain">
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                        <x-icon name="graduation-cap" class="h-6 w-6" />
                    </span>
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-sm leading-tight font-bold text-slate-900">{{ $settings->schoolName() }}</span>
                    <span class="block truncate text-xs text-slate-500">{{ $settings->get('admission_tagline') }}</span>
                </span>
            </a>

            <div class="hidden items-center gap-1 xl:flex">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                           'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                           'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['active']),
                       ])
                       @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            {{-- Signed in: show where to go and how to get out. Showing "Masuk"
                 to someone already signed in sends them to a login page that
                 bounces them straight back, with no way to switch account. --}}
            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <a href="{{ auth()->user()->homeUrl() }}" class="btn-secondary btn-sm">
                        <x-icon name="layout-dashboard" class="h-4 w-4" />
                        {{ auth()->user()->isApplicant() ? 'Pendaftaran Saya' : 'Panel Admin' }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-primary btn-sm">
                            <x-icon name="log-out" class="h-4 w-4" />
                            Keluar
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-secondary btn-sm">
                        <x-icon name="log-in" class="h-4 w-4" />
                        Masuk
                    </a>
                    <a href="{{ route('registration.start') }}" class="btn-primary btn-sm">
                        <x-icon name="user-plus" class="h-4 w-4" />
                        Daftar
                    </a>
                @endauth
            </div>

            <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                    aria-controls="menu-mobile"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 xl:hidden">
                <span class="sr-only">Buka menu navigasi</span>
                <x-icon name="menu" x-show="! open" class="h-6 w-6" />
                <x-icon name="x" x-show="open" x-cloak class="h-6 w-6" />
            </button>
        </div>

        <div id="menu-mobile" x-show="open" x-cloak x-collapse class="xl:hidden">
            <div class="space-y-1 border-t border-slate-200 py-3">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'block rounded-lg px-3 py-2.5 text-sm font-medium',
                           'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                           'text-slate-600 hover:bg-slate-100' => ! request()->routeIs($item['active']),
                       ])>
                        {{ $item['label'] }}
                    </a>
                @endforeach

                <div class="grid grid-cols-2 gap-2 pt-3">
                    @auth
                        <a href="{{ auth()->user()->homeUrl() }}" class="btn-secondary btn-sm">
                            {{ auth()->user()->isApplicant() ? 'Pendaftaran Saya' : 'Panel Admin' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-primary btn-sm w-full">Keluar</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="btn-secondary btn-sm">Masuk</a>
                        <a href="{{ route('registration.start') }}" class="btn-primary btn-sm">Daftar</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
</header>
