<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
    <button type="button" @click="sidebar = true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
        <span class="sr-only">Buka menu</span>
        <x-icon name="menu" class="h-6 w-6" />
    </button>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-900">@yield('title', 'Dashboard')</p>
        @if ($registration?->registration_number)
            <p class="truncate text-xs text-slate-500">No. Pendaftaran {{ $registration->registration_number }}</p>
        @endif
    </div>

    {{-- Notifications --}}
    <div x-data="{ open: false }" class="relative">
        <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                class="relative rounded-lg p-2 text-slate-600 hover:bg-slate-100">
            <span class="sr-only">Notifikasi{{ $unreadCount > 0 ? " ($unreadCount belum dibaca)" : '' }}</span>
            <x-icon name="bell" class="h-5 w-5" />
            @if ($unreadCount > 0)
                <span class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </button>

        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute right-0 z-40 mt-2 w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl bg-white shadow-lg ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('applicant.notifications.read-all') }}">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-brand-600 hover:text-brand-700">Tandai dibaca</button>
                    </form>
                @endif
            </div>

            <div class="max-h-80 overflow-y-auto">
                @forelse ($notifications as $notification)
                    <form method="POST" action="{{ route('applicant.notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" @class([
                            'flex w-full items-start gap-3 border-b border-slate-100 px-4 py-3 text-left last:border-0 hover:bg-slate-50',
                            'bg-brand-50/50' => $notification->isUnread(),
                        ])>
                            <x-icon :name="$notification->icon()" class="mt-0.5 h-4 w-4 shrink-0 text-brand-600" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium text-slate-900">{{ $notification->title }}</span>
                                <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">{{ Str::limit($notification->message, 90) }}</span>
                                <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                            </span>
                        </button>
                    </form>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-500">Belum ada notifikasi.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="hidden items-center gap-2 border-l border-slate-200 pl-3 sm:flex">
        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
            {{ Str::upper(Str::substr($registration?->applicant->full_name ?: '?', 0, 1)) }}
        </span>
        <span class="max-w-36 truncate text-sm font-medium text-slate-700">
            {{ $registration?->applicant->full_name ?: 'Pendaftar' }}
        </span>
    </div>
</header>
