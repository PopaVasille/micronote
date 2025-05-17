<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <meta name="theme-color" content="#38bdf8">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="MicroNote">
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <link rel="icon" type="image/png" sizes="72x72" href="{{ asset('/icons/micronote-icon72x72.png') }}">
        <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('/icons/micronote-icon96x96.png') }}">
        <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('/icons/micronote-icon128x128.png') }}">
        <link rel="icon" type="image/png" sizes="144x144" href="{{ asset('/icons/micronote-icon144x144.png') }}">
        <link rel="icon" type="image/png" sizes="152x152" href="{{ asset('/icons/micronote-icon152x152.png') }}">
        <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('/icons/micronote-icon192x192.png') }}">
        <link rel="icon" type="image/png" sizes="384x384" href="{{ asset('/icons/micronote-icon384x384.png') }}">
        <link rel="icon" type="image/png" sizes="512x512" href="{{ asset('/icons/micronote-icon512x512.png') }}">
        <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('/icons/micronote-icon192x192.png') }}">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
