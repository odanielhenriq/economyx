<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Economyx — Finanças pessoais</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
    <div class="min-h-screen flex">
        {{-- Painel de marca --}}
        <div class="hidden lg:flex lg:w-2/5 flex-col items-center justify-center p-14" style="background-color: #0f1a13;">
            <div class="text-center max-w-sm">
                <div class="flex justify-center mb-6">
                    <x-brand-logo size="lg" />
                </div>
                <p class="text-green-300 text-base leading-relaxed">
                    Controle receitas, despesas, cartões e faturas em um só lugar.
                    Ideal para uso pessoal ou em casal.
                </p>
                <ul class="mt-10 space-y-4 text-left text-sm text-green-100/80">
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Dashboard mensal com fluxo de caixa e faturas
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Importação de extrato com IA (opcional)
                    </li>
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Compartilhamento com parceiro via convite
                    </li>
                </ul>
            </div>
        </div>

        {{-- Área de ação --}}
        <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 bg-white">
            <div class="flex lg:hidden mb-8">
                <x-brand-logo size="sm" variant="light" />
            </div>

            <div class="w-full max-w-md text-center lg:text-left">
                <h1 class="text-2xl font-bold text-slate-900 lg:hidden mb-2">Bem-vindo ao Economyx</h1>
                <p class="text-slate-600 mb-8 lg:hidden">Organize suas finanças com clareza.</p>

                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                            Ir para o Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                            Entrar
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                                Criar conta
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>
</html>
