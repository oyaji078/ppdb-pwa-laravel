@php
    $menu = [
        ['label' => 'Dashboard', 'route' => 'applicant.dashboard', 'icon' => 'layout-dashboard', 'active' => 'applicant.dashboard'],
        ['label' => 'Biodata', 'route' => 'applicant.biodata', 'icon' => 'user-round', 'active' => 'applicant.biodata'],
        ['label' => 'Orang Tua/Wali', 'route' => 'applicant.parents', 'icon' => 'users-round', 'active' => 'applicant.parents'],
        ['label' => 'Asal Sekolah', 'route' => 'applicant.previous-school', 'icon' => 'school', 'active' => 'applicant.previous-school'],
        ['label' => 'Program Pilihan', 'route' => 'applicant.program', 'icon' => 'book-open', 'active' => 'applicant.program'],
        ['label' => 'Berkas', 'route' => 'applicant.documents.index', 'icon' => 'folder-open', 'active' => 'applicant.documents.*'],
        ['label' => 'Pengumuman', 'route' => 'applicant.announcements.index', 'icon' => 'megaphone', 'active' => 'applicant.announcements.*'],
        ['label' => 'Bukti Pendaftaran', 'route' => 'applicant.receipt', 'icon' => 'file-text', 'active' => 'applicant.receipt'],
        ['label' => 'Profil', 'route' => 'applicant.profile', 'icon' => 'settings', 'active' => 'applicant.profile'],
    ];
@endphp

<aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
       aria-label="Navigasi portal pendaftar">
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-4">
        <a href="{{ route('applicant.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="" class="h-9 w-9 rounded-lg object-contain">
            @else
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <x-icon name="graduation-cap" class="h-5 w-5" />
                </span>
            @endif
            <span class="min-w-0">
                <span class="block truncate text-sm font-bold text-slate-900">Portal Pendaftar</span>
                <span class="block truncate text-xs text-slate-500">{{ $settings->admissionName() }}</span>
            </span>
        </a>

        <button type="button" @click="sidebar = false" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 lg:hidden">
            <span class="sr-only">Tutup menu</span>
            <x-icon name="x" class="h-5 w-5" />
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3">
        @foreach ($menu as $item)
            <a href="{{ route($item['route']) }}"
               @class([
                   'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
                   'bg-brand-50 text-brand-700' => request()->routeIs($item['active']),
                   'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($item['active']),
               ])
               @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-3">
        <form method="POST" action="{{ route('applicant.logout') }}">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-rose-600 hover:bg-rose-50">
                <x-icon name="log-out" class="h-5 w-5 shrink-0" />
                Keluar
            </button>
        </form>
    </div>
</aside>
