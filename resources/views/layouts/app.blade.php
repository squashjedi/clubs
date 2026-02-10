<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-dvh bg-zinc-50 dark:bg-zinc-800">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body class="min-h-dvh">
        {{ $slot }}

        @persist("toast")
            <flux:toast.group position="top end">
                <flux:toast class="!w-76" />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
