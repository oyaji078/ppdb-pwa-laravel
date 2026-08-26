{{--
    Applicant login, embeddable on any public page.

    Posts to the same endpoint as the dedicated login page and returns with
    back()->withErrors(), so a failed attempt renders its message right here
    rather than bouncing the visitor somewhere else.

    @param bool $compact  Drops the heading, for use inside a hero panel.
--}}
@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 text-slate-900 shadow-lg ring-1 ring-slate-900/5']) }}>
    @auth
        {{-- Already signed in: a login form here would only bounce them back. --}}
        <h2 class="text-base font-bold text-slate-900">Anda sudah masuk</h2>
        <p class="mt-1 text-sm text-slate-600">
            Masuk sebagai <strong>{{ auth()->user()->name }}</strong>.
        </p>

        <div class="mt-4 space-y-3">
            <a href="{{ auth()->user()->homeUrl() }}" class="btn-primary w-full">
                <x-icon name="arrow-right" class="h-4 w-4" />
                {{ auth()->user()->isApplicant() ? 'Lanjutkan Pendaftaran' : 'Buka Panel Admin' }}
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-secondary w-full">
                    <x-icon name="log-out" class="h-4 w-4" />
                    Keluar &amp; masuk sebagai akun lain
                </button>
            </form>
        </div>
    @else
    @unless ($compact)
        <h2 class="text-base font-bold text-slate-900">Masuk Pendaftar</h2>
    @endunless

    <p class="{{ $compact ? '' : 'mt-1' }} text-sm text-slate-600">
        Lanjutkan pengisian formulir atau periksa status pendaftaran Anda.
    </p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-4 space-y-4">
        @csrf

        <x-form.input name="email" type="email" label="Email" required
                      :value="old('email')" autocomplete="username"
                      placeholder="nama@email.com" />

        <x-form.input name="password" type="password" label="Kata Sandi" required
                      autocomplete="current-password" />

        <button type="submit" class="btn-primary w-full">
            <x-icon name="log-in" class="h-4 w-4" />
            Masuk
        </button>
    </form>

    <p class="mt-4 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
        Belum punya akun?
        <a href="{{ route('registration.start') }}" class="font-semibold text-brand-600 hover:text-brand-700">
            Daftar sekarang
        </a>
    </p>
    @endauth
</div>
