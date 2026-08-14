@php
    use App\Enums\UserRole;

    $user = auth()->user();
    $managesPpdb = $user?->managesPpdb() ?? false;
    $isSuperAdmin = $user?->isSuperAdmin() ?? false;

    $sections = array_values(array_filter([
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'active' => 'admin.dashboard'],
            ],
        ],
        [
            'label' => 'Manajemen PPDB',
            'items' => [
                ['label' => 'Pendaftar', 'route' => 'admin.registrations.index', 'icon' => 'users', 'active' => 'admin.registrations.*'],
                ['label' => 'Verifikasi Berkas', 'route' => 'admin.verification.index', 'icon' => 'file-check', 'active' => 'admin.verification.*'],
                ['label' => 'Seleksi', 'route' => 'admin.selection.index', 'icon' => 'clipboard-list', 'active' => 'admin.selection.*'],
                ['label' => 'Daftar Ulang', 'route' => 'admin.reregistration.index', 'icon' => 'clipboard-check', 'active' => 'admin.reregistration.*'],
                ['label' => 'Laporan', 'route' => 'admin.reports.index', 'icon' => 'chart-column', 'active' => 'admin.reports.*'],
            ],
        ],
        $managesPpdb ? [
            'label' => 'Konfigurasi PPDB',
            'items' => [
                ['label' => 'Tahun Ajaran', 'route' => 'admin.academic-years.index', 'icon' => 'calendar-days', 'active' => 'admin.academic-years.*'],
                ['label' => 'Gelombang', 'route' => 'admin.waves.index', 'icon' => 'layers', 'active' => 'admin.waves.*'],
                ['label' => 'Jalur', 'route' => 'admin.tracks.index', 'icon' => 'route', 'active' => 'admin.tracks.*'],
                ['label' => 'Program', 'route' => 'admin.programs.index', 'icon' => 'book-open', 'active' => 'admin.programs.*'],
                ['label' => 'Persyaratan Berkas', 'route' => 'admin.document-types.index', 'icon' => 'file-stack', 'active' => 'admin.document-types.*'],
                ['label' => 'Jadwal', 'route' => 'admin.schedules.index', 'icon' => 'calendar-clock', 'active' => 'admin.schedules.*'],
            ],
        ] : null,
        $managesPpdb ? [
            'label' => 'Konten Website',
            'items' => [
                ['label' => 'Pengumuman', 'route' => 'admin.announcements.index', 'icon' => 'megaphone', 'active' => 'admin.announcements.*'],
                ['label' => 'Berita', 'route' => 'admin.news.index', 'icon' => 'newspaper', 'active' => 'admin.news.*'],
                ['label' => 'Galeri', 'route' => 'admin.galleries.index', 'icon' => 'images', 'active' => 'admin.galleries.*'],
                ['label' => 'Fasilitas', 'route' => 'admin.facilities.index', 'icon' => 'building-2', 'active' => 'admin.facilities.*'],
                ['label' => 'Unduhan', 'route' => 'admin.downloads.index', 'icon' => 'download', 'active' => 'admin.downloads.*'],
                ['label' => 'Profil Sekolah', 'route' => 'admin.school-profile.index', 'icon' => 'landmark', 'active' => 'admin.school-profile.*'],
            ],
        ] : null,
        [
            'label' => 'Sistem',
            'items' => array_values(array_filter([
                $isSuperAdmin ? ['label' => 'Admin', 'route' => 'admin.users.index', 'icon' => 'user-cog', 'active' => 'admin.users.*'] : null,
                $isSuperAdmin ? ['label' => 'Pengaturan', 'route' => 'admin.settings.edit', 'icon' => 'settings', 'active' => 'admin.settings.*'] : null,
                $managesPpdb ? ['label' => 'Activity Log', 'route' => 'admin.activity-log.index', 'icon' => 'history', 'active' => 'admin.activity-log.*'] : null,
                ['label' => 'Profil', 'route' => 'admin.profile.edit', 'icon' => 'circle-user', 'active' => 'admin.profile.*'],
            ])),
        ],
    ]));
@endphp

<aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900 transition-transform duration-200 lg:translate-x-0"
       aria-label="Navigasi panel admin">
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-800 px-4">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg bg-white object-contain p-0.5">
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="graduation-cap" class="h-5 w-5" />
                </span>
            @endif
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-white">Panel Admin</span>
                <span class="block truncate text-xs text-slate-400">{{ $settings->admissionName() }}</span>
            </span>
        </a>

        <button type="button" @click="sidebar = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 lg:hidden">
            <span class="sr-only">Tutup menu</span>
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-5 overflow-y-auto p-3">
        @foreach ($sections as $section)
            <div>
                @if ($section['label'])
                    <p class="px-3 pb-2 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ $section['label'] }}</p>
                @endif

                <div class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                               'bg-brand-600 text-white' => request()->routeIs($item['active']),
                               'text-slate-300 hover:bg-slate-800 hover:text-white' => ! request()->routeIs($item['active']),
                           ])
                           @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                            <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="border-t border-slate-800 p-3">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-rose-400 hover:bg-slate-800">
                <x-icon name="log-out" class="h-5 w-5 shrink-0" />
                Logout
            </button>
        </form>
    </div>
</aside>
