<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}" class="dark h-full bg-white">
    <head>
        @include("partials.head")
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800 text-sm">

            <main class="py-10">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-5xl">
                        {{ $slot }}
                    </div>
                </div>
            </main>

        @persist("toast")
            <flux:toast.group position="top end">
                <flux:toast class="!w-76" />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
