{{--
    Shown above every form step once the applicant is logged in: who they are,
    the number they were issued at sign-up, and whether their e-mail still needs
    confirming. Also carries the legend for the required-field asterisk.
--}}
@props(['registration'])

@php
    $applicant = $registration->applicant;
    $needsVerification = filled($applicant?->email) && ! $applicant->hasVerifiedEmail();
@endphp

<div class="card p-4 sm:p-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $applicant?->full_name ?: 'Calon Peserta Didik' }}</p>
            <p class="mt-0.5 text-xs text-slate-500">
                Nomor Pendaftaran
                <span class="font-mono font-semibold tracking-wider text-slate-700">{{ $registration->registration_number }}</span>
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="btn-secondary btn-sm">
                <x-icon name="log-out" class="h-3.5 w-3.5" />
                Keluar
            </button>
        </form>
    </div>

    <p class="mt-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
        Tanda <span class="text-rose-600" aria-hidden="true">*</span> menandakan isian wajib.
    </p>

    @if ($needsVerification)
        <div class="mt-3 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200 ring-inset">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-amber-900">
                    Email <strong>{{ $applicant->email }}</strong> belum diverifikasi.
                    Periksa kotak masuk Anda, termasuk folder spam.
                </p>

                <form method="POST" action="{{ route('registration.email.resend') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="btn-secondary btn-sm">
                        <x-icon name="send" class="h-3.5 w-3.5" />
                        Kirim Ulang
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
