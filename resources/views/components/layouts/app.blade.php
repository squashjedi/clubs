<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full bg-white">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 text-base sm:text-sm">

        {{ $slot }}

        @fluxScripts

        @persist('toast')
            <flux:toast />
        @endpersist
    </body>
</html>