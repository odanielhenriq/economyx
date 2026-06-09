<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Economyx') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex">

            {{-- Painel de marca --}}
            <div class="hidden lg:flex lg:w-2/5 flex-col items-center justify-center p-14"
                 style="background-color: #0f1a13;">
                <div class="text-center max-w-xs">
                    <div class="flex justify-center mb-6">
                        <x-brand-logo size="lg" />
                    </div>
                    <p class="text-green-300 text-base leading-relaxed">
                        Controle suas finanças com clareza.<br>
                        Para você e para quem mora com você.
                    </p>

                    <ul class="mt-12 space-y-4 text-left text-sm text-green-100/80">
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Dashboard com receitas, despesas e faturas
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Parcelas e contas fixas automáticas
                        </li>
                        <li class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Compartilhe finanças com quem mora com você
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Formulário --}}
            <div class="flex-1 flex flex-col items-center justify-center px-8 py-12 bg-white">
                <div class="flex lg:hidden mb-8">
                    <x-brand-logo size="sm" variant="light" />
                </div>

                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
