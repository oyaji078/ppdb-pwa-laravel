@extends('layouts.admin')

@section('title', 'Profil Saya')
@section('subheading', 'Perbarui informasi akun dan kata sandi Anda.')

@section('content')
    <div class="max-w-2xl space-y-5">
        <form method="POST" action="{{ route('admin.profile.update') }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-slate-900">Informasi Akun</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input name="name" label="Nama Lengkap" required :value="$user->name" />
                </div>

                <x-form.input name="email" type="email" label="Email" required :value="$user->email" />

                <x-form.input name="phone" label="Nomor HP" :value="$user->phone" inputmode="tel" />
            </div>

            <dl class="mt-5 grid gap-4 rounded-lg bg-slate-50 p-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-slate-500">Nama Pengguna</dt>
                    <dd class="mt-0.5 font-mono text-sm text-slate-900">{{ $user->username }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Peran</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">{{ $user->role->label() }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-slate-500">Login Terakhir</dt>
                    <dd class="mt-0.5 text-sm text-slate-900">
                        {{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}
                    </dd>
                </div>
            </dl>

            <button type="submit" class="btn-primary mt-6">
                <x-icon name="save" class="h-4 w-4" />
                Simpan Profil
            </button>
        </form>

        <form method="POST" action="{{ route('admin.profile.password') }}" class="card p-6">
            @csrf
            @method('PUT')

            <h2 class="font-semibold text-slate-900">Ganti Kata Sandi</h2>
            <p class="prose-content mt-1">Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form.input name="current_password" type="password" label="Kata Sandi Saat Ini" required
                                  autocomplete="current-password" />
                </div>

                <x-form.input name="password" type="password" label="Kata Sandi Baru" required
                              autocomplete="new-password" hint="Minimal 8 karakter, mengandung huruf dan angka." />

                <x-form.input name="password_confirmation" type="password" label="Konfirmasi Kata Sandi Baru" required
                              autocomplete="new-password" />
            </div>

            <button type="submit" class="btn-primary mt-6">
                <x-icon name="key-round" class="h-4 w-4" />
                Ganti Kata Sandi
            </button>
        </form>
    </div>
@endsection
