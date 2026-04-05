<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Economyx') }}</title>

    <!-- DM Sans — fonte principal -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased" style="background-color: #f8fafc;">

<div class="flex min-h-screen">

    {{-- ===== SIDEBAR ===== --}}
    <aside class="fixed inset-y-0 left-0 z-30 w-64 flex flex-col"
           style="background-color: #0f1a13;">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10 flex-shrink-0">
            <div class="w-8 h-8 bg-green-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold text-sm leading-none">E$</span>
            </div>
            <span class="text-white font-semibold text-lg tracking-tight">Economyx</span>
        </div>

        {{-- Navegação principal --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard.monthly') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard*')
                         ? 'bg-green-600 text-white'
                         : 'text-green-100/70 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            {{-- Seção Finanças --}}
            <div class="pt-5 pb-1.5 px-3">
                <span class="text-[10px] font-semibold uppercase tracking-widest" style="color: rgba(209,250,229,0.35);">
                    Finanças
                </span>
            </div>

            {{-- Transações --}}
            <a href="{{ route('transactions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('transactions*')
                         ? 'bg-green-600 text-white'
                         : 'text-green-100/70 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Transações
            </a>

            {{-- Cartões --}}
            <a href="{{ route('cards.statement.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('cards*')
                         ? 'bg-green-600 text-white'
                         : 'text-green-100/70 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Cartões
            </a>

            {{-- Contas fixas --}}
            <a href="{{ route('recurring-transactions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                      {{ request()->routeIs('recurring-transactions*')
                         ? 'bg-green-600 text-white'
                         : 'text-green-100/70 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Contas fixas
            </a>

            {{-- Seção Configurações --}}
            <div class="pt-5 pb-1.5 px-3">
                <span class="text-[10px] font-semibold uppercase tracking-widest" style="color: rgba(209,250,229,0.35);">
                    Configurações
                </span>
            </div>

            {{-- Configurações colapsável --}}
            @php
                $inSettings = request()->routeIs('categories.*')
                    || request()->routeIs('types.*')
                    || request()->routeIs('payment-methods.*')
                    || request()->routeIs('credit-cards.*')
                    || request()->routeIs('recurring-templates.*')
                    || request()->routeIs('budgets.*');
            @endphp
            <div x-data="{ open: {{ $inSettings ? 'true' : 'false' }} }">
                <button @click="open = !open"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                               text-green-100/70 hover:bg-white/5 hover:text-white">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="flex-1 text-left">Configurações</span>
                    <svg class="w-3.5 h-3.5 transition-transform duration-200 flex-shrink-0"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="mt-0.5 space-y-0.5 pl-7"
                     style="display:none">

                    @php
                        $subLink = fn($route, $label) =>
                            '<a href="' . route($route) . '" '
                            . 'class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-medium transition-colors '
                            . (request()->routeIs(str_replace('.index', '.*', $route))
                               ? 'bg-green-600/20 text-green-300'
                               : 'text-green-100/50 hover:bg-white/5 hover:text-white')
                            . '">' . $label . '</a>';
                    @endphp

                    <a href="{{ route('categories.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('categories.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Categorias
                    </a>
                    <a href="{{ route('types.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('types.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Tipos
                    </a>
                    <a href="{{ route('payment-methods.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('payment-methods.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Formas de pagamento
                    </a>
                    <a href="{{ route('credit-cards.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('credit-cards.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Cartões
                    </a>
                    <a href="{{ route('recurring-templates.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('recurring-templates.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Recorrências
                    </a>
                    <a href="{{ route('budgets.index') }}"
                       class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                              {{ request()->routeIs('budgets.*') ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                        Orçamentos
                    </a>
                </div>
            </div>

        </nav>

        {{-- Usuário + logout --}}
        <div class="px-4 py-4 border-t border-white/10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center
                            text-white text-xs font-semibold flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-xs truncate" style="color: rgba(209,250,229,0.5);">
                        {{ auth()->user()->email ?? '' }}
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="transition-colors hover:text-white"
                            style="color: rgba(209,250,229,0.4);"
                            title="Sair">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </aside>

    {{-- ===== CONTEÚDO PRINCIPAL ===== --}}
    <div class="ml-64 flex-1 flex flex-col min-h-screen" style="background-color: #f8fafc;">

        {{-- Topbar --}}
        @isset($header)
            <header class="sticky top-0 z-20 bg-white border-b px-8 py-4 flex-shrink-0"
                    style="border-color: #e2e8f0;">
                {{ $header }}
            </header>
        @endisset

        {{-- Conteúdo --}}
        <main class="flex-1 px-8 py-6">
            {{ $slot }}
        </main>

    </div>

</div>

<x-toast-container />
<x-confirm-delete-modal />

</body>
</html>
