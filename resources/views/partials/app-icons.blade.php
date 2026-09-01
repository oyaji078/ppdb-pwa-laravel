{{--
    Favicon and iOS home-screen icon, both rendered from the logo the school
    uploaded in the admin settings. The ?v= fingerprint changes with the logo,
    so a replacement shows up immediately instead of behind a cached copy.
--}}
@php($iconVersion = $settings->iconVersion())

<link rel="icon" href="{{ route('pwa.icon', ['size' => 32, 'v' => $iconVersion]) }}" sizes="32x32" type="image/png">
<link rel="icon" href="{{ route('pwa.icon', ['size' => 192, 'v' => $iconVersion]) }}" sizes="192x192" type="image/png">
<link rel="apple-touch-icon" href="{{ route('pwa.icon', ['size' => 180, 'v' => $iconVersion]) }}">
<meta name="apple-mobile-web-app-title" content="{{ $settings->get('school_short_name') ?? $settings->schoolName() }}">
