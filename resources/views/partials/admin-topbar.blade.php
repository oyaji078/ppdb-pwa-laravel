@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
    <button type="button" @click="sidebar = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
        <span class="sr-only">Buka menu</span>
        <x-icon name="menu" class="h-6 w-6" />
    </button>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-900">{{ $settings->schoolName() }}</p>
        <p class="truncate text-xs text-slate-500">{{ $settings->admissionName() }} &mdash; Panel Panitia</p>
    </div>

    <a href="{{ route('home') }}" target="_blank" rel="noopener"
       class="hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100 sm:block" title="Lihat website publik">
        <span class="sr-only">Lihat website publik</span>
        <x-icon name="external-link" class="h-5 w-5" />
    </a>

    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                class="flex items-center gap-2 rounded-lg py-1.5 pr-2 pl-1.5 hover:bg-slate-100">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                {{ Str::upper(Str::substr($user?->name ?? '?', 0, 1)) }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block max-w-32 truncate text-sm font-medium text-slate-800">{{ $user?->name }}</span>
                <span class="block text-xs text-slate-500">{{ $user?->role->label() }}</span>
            </span>
            <x-icon name="chevron-down" class="h-4 w-4 text-slate-400" />
        </button>

        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user?->email }}</p>
            </div>

            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                <x-icon name="circle-user" class="h-4 w-4 text-slate-400" />
                Profil Saya
            </a>

            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50">
                    <x-icon name="log-out" class="h-4 w-4" />
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>
