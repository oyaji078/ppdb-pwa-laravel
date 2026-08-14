@extends('layouts.admin')

@section('title', $user->exists ? 'Edit Admin' : 'Tambah Admin')

@section('actions')
    <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-sm">
        <x-icon name="arrow-left" class="h-4 w-4" />
        Kembali
    </a>
@endsection

@section('content')
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
          class="card max-w-2xl p-6">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="Nama Lengkap" required :value="$user->name" />

            <x-form.input name="username" label="Nama Pengguna" required :value="$user->username"
                          autocomplete="off" hint="Huruf, angka, tanda hubung, dan garis bawah." />

            <x-form.input name="email" type="email" label="Email" required :value="$user->email" autocomplete="off" />

            <x-form.input name="phone" label="Nomor HP" :value="$user->phone" inputmode="tel" />

            @php $isSelf = $user->exists && $user->id === auth()->id(); @endphp

            @if ($isSelf)
                {{-- Own account: role and active flag are fixed, and the
                     controller ignores these fields anyway. --}}
                <div>
                    <p class="form-label">Peran</p>
                    <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-700">{{ $user->role->label() }}</p>
                </div>

                <div>
                    <p class="form-label">Status Akun</p>
                    <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-700">Aktif</p>
                </div>
            @else
                <x-form.select name="role" label="Peran" required :placeholder="null"
                               :value="$user->role?->value" :options="$roles"
                               hint="Verifikator hanya dapat memverifikasi berkas. Admin PPDB dapat mengelola konfigurasi dan seleksi." />

                <div class="flex items-end pb-1">
                    <x-form.checkbox name="is_active" label="Akun aktif"
                                     :checked="$user->exists ? $user->is_active : true" />
                </div>
            @endif

            <div class="sm:col-span-2">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ $user->exists ? 'Ganti Kata Sandi' : 'Kata Sandi' }}
                    </p>
                    @if ($user->exists)
                        <p class="mt-0.5 text-xs text-slate-500">Kosongkan bila tidak ingin mengubah kata sandi.</p>
                    @endif

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-form.input name="password" type="password" label="Kata Sandi"
                                      :required="! $user->exists" autocomplete="new-password"
                                      hint="Minimal 8 karakter, mengandung huruf dan angka." />

                        <x-form.input name="password_confirmation" type="password" label="Konfirmasi Kata Sandi"
                                      :required="! $user->exists" autocomplete="new-password" />
                    </div>
                </div>
            </div>
        </div>

        @if ($isSelf)
            <x-alert type="info" class="mt-5">
                Anda tidak dapat mengubah peran atau menonaktifkan akun Anda sendiri.
            </x-alert>
        @endif

        <div class="mt-6 flex gap-3 border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">
                <x-icon name="save" class="h-4 w-4" />
                Simpan
            </button>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>
@endsection
