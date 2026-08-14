@extends('layouts.public')

@section('title', 'Kontak')

@section('hero')
    <x-page-header title="Kontak Panitia"
                   :subtitle="'Hubungi panitia '.$settings->admissionName().' untuk pertanyaan seputar pendaftaran.'"
                   :breadcrumb="['Kontak' => null]" />
@endsection

@section('content')
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-5 sm:grid-cols-2">
            @php
                $contacts = array_values(array_filter([
                    $settings->get('school_address') ? ['map-pin', 'Alamat', $settings->get('school_address'), null] : null,
                    $settings->get('school_phone') ? ['phone', 'Telepon', $settings->get('school_phone'), 'tel:'.$settings->get('school_phone')] : null,
                    $settings->get('contact_email') ? ['mail', 'Email Panitia', $settings->get('contact_email'), 'mailto:'.$settings->get('contact_email')] : null,
                    $settings->get('contact_whatsapp') ? ['message-circle', 'WhatsApp', $settings->get('contact_whatsapp'), $settings->whatsappUrl()] : null,
                    $settings->get('school_email') ? ['at-sign', 'Email Sekolah', $settings->get('school_email'), 'mailto:'.$settings->get('school_email')] : null,
                    $settings->get('school_website') ? ['globe', 'Website', $settings->get('school_website'), $settings->get('school_website')] : null,
                ]));
            @endphp

            @forelse ($contacts as [$icon, $label, $value, $url])
                <div class="card p-5">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                        <x-icon :name="$icon" class="h-5 w-5" />
                    </span>
                    <p class="mt-4 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $label }}</p>
                    @if ($url)
                        <a href="{{ $url }}" @if (Str::startsWith($url, 'http')) target="_blank" rel="noopener" @endif
                           class="mt-1 block text-sm font-medium break-words text-brand-700 hover:text-brand-800">{{ $value }}</a>
                    @else
                        <p class="mt-1 text-sm text-slate-700">{{ $value }}</p>
                    @endif
                </div>
            @empty
                <div class="card sm:col-span-2">
                    <x-empty-state icon="phone-off" title="Kontak belum diatur"
                                   description="Panitia belum melengkapi informasi kontak." />
                </div>
            @endforelse
        </div>

        <div class="card mt-6 p-6">
            <h2 class="font-semibold text-slate-900">Jam Layanan Panitia</h2>
            <p class="prose-content mt-2">
                Layanan informasi {{ $settings->admissionName() }} tersedia pada hari kerja. Untuk pertanyaan di luar jam layanan,
                silakan kirim pesan melalui email atau WhatsApp dan panitia akan membalas pada hari kerja berikutnya.
            </p>
        </div>
    </div>
@endsection
