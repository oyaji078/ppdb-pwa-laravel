@extends('layouts.admin')

@section('title', 'Admin')
@section('subheading', 'Akun panitia yang dapat mengakses panel admin.')

@section('actions')
    <a href="{{ route('admin.users.create') }}" class="btn-primary btn-sm">
        <x-icon name="plus" class="h-4 w-4" />
        Tambah Admin
    </a>
@endsection

@section('content')
    <div class="space-y-5">
        <form method="GET" action="{{ route('admin.users.index') }}" class="card flex flex-wrap items-end gap-3 p-4">
            <div class="min-w-56 flex-1">
                <x-form.input name="q" label="Cari" :value="request('q')" placeholder="Nama, username, atau email" />
            </div>
            <div class="min-w-48">
                <x-form.select name="role" label="Peran" placeholder="Semua peran" :value="request('role')" :options="$roles" />
            </div>
            <button type="submit" class="btn-secondary">
                <x-icon name="filter" class="h-4 w-4" />
                Terapkan
            </button>
        </form>

        <div class="card overflow-hidden">
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Nama</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Peran</th>
                            <th scope="col">Login Terakhir</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Aksi</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>
                                    <span class="flex items-center gap-2.5">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700">
                                            {{ Str::upper(Str::substr($user->name, 0, 1)) }}
                                        </span>
                                        <span class="font-medium text-slate-900">{{ $user->name }}</span>
                                    </span>
                                </td>
                                <td class="font-mono text-xs">{{ $user->username }}</td>
                                <td class="max-w-48 truncate">{{ $user->email }}</td>
                                <td>
                                    <x-badge class="bg-indigo-50 text-indigo-700 ring-indigo-200">{{ $user->role->label() }}</x-badge>
                                </td>
                                <td class="whitespace-nowrap">
                                    {{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <x-badge class="bg-emerald-50 text-emerald-700 ring-emerald-200">Aktif</x-badge>
                                    @else
                                        <x-badge class="bg-rose-50 text-rose-700 ring-rose-200">Nonaktif</x-badge>
                                    @endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary btn-sm">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>

                                        @if ($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                  onsubmit="return confirm('Hapus akun {{ $user->name }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-secondary btn-sm text-rose-600">
                                                    <x-icon name="trash-2" class="h-3.5 w-3.5" />
                                                    <span class="sr-only">Hapus</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        </div>
    </div>
@endsection
