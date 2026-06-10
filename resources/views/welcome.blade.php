<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Economyx — controle financeiro pessoal com cartões, parcelas, orçamentos e visão mensal.">
    <title>Economyx — Organize seu dinheiro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased text-slate-900">

    {{-- Header --}}
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="/" class="flex items-center gap-2">
                <x-brand-logo size="sm" variant="light" />
            </a>
            <nav class="flex items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition">
                        Ir para o dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden sm:inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition">
                        Entrar
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 transition">
                            Começar agora
                        </a>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-slate-50"></div>
        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-20">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-800">
                    Finanças pessoais simplificadas
                </p>
                <h1 class="mt-5 text-3xl font-bold leading-tight text-slate-900 sm:text-4xl lg:text-5xl">
                    Organize seu dinheiro sem complicar sua rotina.
                </h1>
                <p class="mt-5 text-base leading-relaxed text-slate-600 sm:text-lg">
                    Acompanhe gastos, cartões, parcelas, orçamentos e faturas em uma visão simples e moderna — sozinho ou com quem divide a casa.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                            Abrir dashboard
                        </a>
                    @else
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                                Começar agora — é grátis
                            </a>
                        @endif
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Já tenho conta
                        </a>
                    @endauth
                </div>
            </div>

            {{-- Dashboard preview --}}
            <div class="relative">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-200/60 sm:p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-800">Dashboard — Junho</span>
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700">Prévia</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Receitas</p>
                            <p class="mt-1 text-lg font-bold text-emerald-700 tabular-nums">R$ 8.420</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Despesas</p>
                            <p class="mt-1 text-lg font-bold text-red-600 tabular-nums">R$ 5.180</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Saldo</p>
                            <p class="mt-1 text-lg font-bold text-emerald-700 tabular-nums">R$ 3.240</p>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                            <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">A pagar</p>
                            <p class="mt-1 text-lg font-bold text-slate-800 tabular-nums">R$ 1.290</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl border border-slate-100 p-3">
                        <p class="text-xs font-semibold text-slate-700 mb-3">Onde o dinheiro foi</p>
                        <div class="flex items-center gap-4">
                            <div class="h-20 w-20 shrink-0 rounded-full" style="background: conic-gradient(#16a34a 0% 42%, #3b82f6 42% 68%, #f59e0b 68% 100%);"></div>
                            <div class="flex-1 space-y-1.5 text-xs">
                                <div class="flex justify-between"><span class="text-slate-600">Moradia</span><span class="font-medium tabular-nums">42%</span></div>
                                <div class="flex justify-between"><span class="text-slate-600">Alimentação</span><span class="font-medium tabular-nums">26%</span></div>
                                <div class="flex justify-between"><span class="text-slate-600">Transporte</span><span class="font-medium tabular-nums">32%</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Benefícios --}}
    <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Tudo o que você precisa no dia a dia</h2>
            <p class="mt-3 text-slate-600">Sem planilhas complicadas. Um painel claro para decidir melhor.</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941', 'title' => 'Veja para onde vai seu dinheiro', 'desc' => 'Dashboard mensal com receitas, despesas, saldo e gráfico por categoria.'],
                ['icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z', 'title' => 'Faturas e parcelas', 'desc' => 'Acompanhe faturas de cartão e compras parceladas sem perder prazos.'],
                ['icon' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z', 'title' => 'Orçamentos por categoria', 'desc' => 'Defina limites mensais e receba alertas quando estiver perto do teto.'],
                ['icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'title' => 'Divida com parceiros', 'desc' => 'Convide quem mora com você e veja gastos compartilhados na mesma rede.'],
                ['icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', 'title' => 'Contas fixas', 'desc' => 'Cadastre aluguel, assinaturas e despesas recorrentes uma vez só.'],
                ['icon' => 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5', 'title' => 'Importe extratos', 'desc' => 'Traga movimentações de forma mais rápida com importação assistida por IA (opcional).'],
            ] as $benefit)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100">
                        <svg class="h-5 w-5 text-green-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefit['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $benefit['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $benefit['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA final --}}
    <section class="border-t border-slate-200" style="background-color: #0f1a13;">
        <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 text-center">
            <div class="flex justify-center mb-6">
                <x-brand-logo size="md" />
            </div>
            <h2 class="text-2xl font-bold text-white sm:text-3xl">Comece hoje sua organização financeira</h2>
            <p class="mt-3 text-green-100/80 max-w-xl mx-auto">Cadastre-se em minutos e tenha clareza sobre receitas, despesas e cartões.</p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                        Ir para o dashboard
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center justify-center rounded-lg bg-green-600 px-6 py-3 text-sm font-semibold text-white hover:bg-green-700 transition">
                            Criar conta gratuita
                        </a>
                    @endif
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-white/20 px-6 py-3 text-sm font-semibold text-white hover:bg-white/5 transition">
                        Entrar
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        Economyx — controle financeiro pessoal
    </footer>
</body>
</html>
