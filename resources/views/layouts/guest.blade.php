<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Economyx') }}</title>

        <!-- DM Sans -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            {{-- Left panel — dark green brand area (hidden on mobile) --}}
            <div class="hidden lg:flex lg:w-2/5 flex-col items-center justify-center p-14"
                 style="background-color: #0f1a13;">
                <div class="text-center">
                    {{-- Logo badge --}}
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-green-500 mb-6">
                        <span class="text-2xl font-bold text-white tracking-tight">E$</span>
                    </div>

                    <h1 class="text-3xl font-bold text-white mb-3">Economyx</h1>
                    <p class="text-green-300 text-base leading-relaxed max-w-xs">
                        Controle suas finanças com clareza.<br>
                        Para você e para quem mora com você.
                    </p>

                    <div class="mt-12 grid grid-cols-3 gap-6 text-center">
                        <div>
                            <div class="text-2xl font-bold text-white tabular-nums">0%</div>
                            <div class="text-xs text-green-400 mt-1">Custo</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-white">∞</div>
                            <div class="text-xs text-green-400 mt-1">Transações</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-white">1</div>
                            <div class="text-xs text-green-400 mt-1">Painel</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right panel — white form area --}}
            <div class="flex-1 flex flex-col items-center justify-center px-8 py-12 bg-white">

                {{-- Mobile logo --}}
                <div class="flex lg:hidden items-center gap-2 mb-8">
                    <div class="w-9 h-9 rounded-xl bg-green-500 flex items-center justify-center">
                        <span class="text-sm font-bold text-white">E$</span>
                    </div>
                    <span class="text-lg font-bold text-slate-900">Economyx</span>
                </div>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
