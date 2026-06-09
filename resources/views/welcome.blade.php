<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Economyx — Finanças pessoais</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
        <div class="w-full max-w-lg text-center">
            <div class="mb-8 inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-green-600 text-2xl font-bold text-white shadow-lg">
                E
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Economyx</h1>
            <p class="mt-3 text-base text-slate-600 leading-relaxed">
                Controle receitas, despesas, cartões e faturas em um só lugar.
                Ideal para uso pessoal ou em casal.
            </p>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:justify-center">
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

            <ul class="mt-12 space-y-3 text-left text-sm text-slate-600">
                <li class="flex items-start gap-2">
                    <span class="text-green-600 font-bold">✓</span>
                    Dashboard mensal com fluxo de caixa e faturas de cartão
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-600 font-bold">✓</span>
                    Importação de extrato com IA (opcional)
                </li>
                <li class="flex items-start gap-2">
                    <span class="text-green-600 font-bold">✓</span>
                    Compartilhamento com parceiro via convite
                </li>
            </ul>
        </div>
    </div>
</body>
</html>
