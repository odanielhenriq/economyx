<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Economyx') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        if (localStorage.getItem('economyx_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="appShell()"
    x-on:keydown.escape.window="sidebarOpen = false"
    x-on:open-sidebar.window="sidebarOpen = true"
    class="font-sans antialiased"
    style="background-color: #f8fafc;">

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
        ]"
        class="fixed inset-y-0 left-0 z-40 w-64 flex flex-col
               transform transition-all duration-300 ease-in-out"
        style="background-color: #0f1a13;">

        <div class="flex items-center justify-between gap-2 px-3 py-4 border-b border-white/10 flex-shrink-0"
             :class="sidebarCollapsed ? 'lg:px-2 lg:justify-center' : 'lg:px-4'">
            <div :class="sidebarCollapsed ? 'lg:hidden' : ''" class="min-w-0">
                <x-brand-logo size="sm" />
            </div>
            <div :class="sidebarCollapsed ? 'hidden lg:flex' : 'hidden'" class="justify-center w-full">
                <div class="w-9 h-9 rounded-xl bg-green-500 flex items-center justify-center">
                    <span class="font-bold text-white text-sm">E$</span>
                </div>
            </div>
            <button type="button"
                    @click="toggleSidebarCollapsed()"
                    class="hidden lg:inline-flex p-2 rounded-lg text-green-100/50 hover:text-white hover:bg-white/5 transition"
                    :title="sidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
                    aria-label="Alternar menu lateral">
                <svg class="w-4 h-4 transition-transform" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
        </div>

        <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto overflow-x-hidden">

            @php
                $navLink = fn (bool $active) => $active
                    ? 'bg-green-600 text-white'
                    : 'text-green-100/70 hover:bg-white/5 hover:text-white';
                $navClass = 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors';
            @endphp

            <a href="{{ route('dashboard.monthly') }}"
               @click="sidebarOpen = false"
               title="Dashboard"
               class="{{ $navClass }} {{ $navLink(request()->routeIs('dashboard*')) }}"
               :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Dashboard</span>
            </a>

            <div class="pt-4 pb-1 px-3" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                <span class="text-[10px] font-semibold uppercase tracking-widest" style="color: rgba(209,250,229,0.35);">Finanças</span>
            </div>

            @foreach ([
                ['route' => 'transactions.index', 'match' => 'transactions*', 'label' => 'Transações', 'title' => 'Transações', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['route' => 'cards.statement.index', 'match' => 'cards*', 'label' => 'Faturas do cartão', 'title' => 'Faturas do cartão', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                ['route' => 'recurring-templates.index', 'match' => 'recurring-templates*', 'label' => 'Contas fixas', 'title' => 'Contas fixas', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ['route' => 'installment-purchases.index', 'match' => 'installment-purchases*', 'label' => 'Compras parceladas', 'title' => 'Compras parceladas', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                ['route' => 'shared-expenses.index', 'match' => 'shared-expenses*', 'label' => 'Gastos compartilhados', 'title' => 'Gastos compartilhados', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ] as $item)
                <a href="{{ route($item['route']) }}"
                   @click="sidebarOpen = false"
                   title="{{ $item['title'] }}"
                   class="{{ $navClass }} {{ $navLink(request()->routeIs($item['match'])) }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <div class="pt-4 pb-1 px-3" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                <span class="text-[10px] font-semibold uppercase tracking-widest" style="color: rgba(209,250,229,0.35);">Configurações</span>
            </div>

            @php
                $inSettings = request()->routeIs('partners.*')
                    || request()->routeIs('categories.*')
                    || request()->routeIs('types.*')
                    || request()->routeIs('payment-methods.*')
                    || request()->routeIs('credit-cards.*')
                    || request()->routeIs('budgets.*');
            @endphp
            <div x-data="{ open: {{ $inSettings ? 'true' : 'false' }} }">
                <button type="button"
                        @click="$root.sidebarCollapsed ? ($root.toggleSidebarCollapsed(), open = true) : (open = !open)"
                        title="Configurações"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-green-100/70 hover:bg-white/5 hover:text-white"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="flex-1 text-left" :class="sidebarCollapsed ? 'lg:hidden' : ''">Configurações</span>
                    <svg class="w-3.5 h-3.5 transition-transform flex-shrink-0" :class="[open ? 'rotate-180' : '', sidebarCollapsed ? 'lg:hidden' : '']"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="open && !$root.sidebarCollapsed"
                     x-transition
                     class="mt-0.5 space-y-0.5 pl-7"
                     style="display:none">
                    @foreach ([
                        ['route' => 'partners.index', 'match' => 'partners.*', 'label' => 'Parceiros'],
                        ['route' => 'categories.index', 'match' => 'categories.*', 'label' => 'Categorias'],
                        ['route' => 'types.index', 'match' => 'types.*', 'label' => 'Tipos'],
                        ['route' => 'payment-methods.index', 'match' => 'payment-methods.*', 'label' => 'Formas de pagamento'],
                        ['route' => 'credit-cards.index', 'match' => 'credit-cards.*', 'label' => 'Meus cartões'],
                        ['route' => 'budgets.index', 'match' => 'budgets.*', 'label' => 'Orçamentos'],
                    ] as $sub)
                        <a href="{{ route($sub['route']) }}"
                           @click="sidebarOpen = false"
                           class="flex items-center px-3 py-2 rounded-lg text-xs font-medium transition-colors
                                  {{ request()->routeIs($sub['match']) ? 'bg-green-600/20 text-green-300' : 'text-green-100/50 hover:bg-white/5 hover:text-white' }}">
                            {{ $sub['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @if (auth()->user()->isDev())
                <div class="pt-4 pb-1 px-3" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <span class="text-[10px] font-semibold uppercase tracking-widest" style="color: rgba(209,250,229,0.35);">Administração</span>
                </div>
                <a href="{{ route('admin.users.index') }}"
                   @click="sidebarOpen = false"
                   title="Usuários"
                   class="{{ $navClass }} {{ $navLink(request()->routeIs('admin.*')) }}"
                   :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">Usuários</span>
                </a>
            @endif
        </nav>

        <div class="px-3 py-3 border-t border-white/10 flex-shrink-0"
             :class="sidebarCollapsed ? 'lg:px-2' : ''">
            <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
                <div class="w-8 h-8 rounded-full bg-green-600 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0"
                     title="{{ auth()->user()->name ?? '' }}">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? '' }}</p>
                    <p class="text-xs truncate" style="color: rgba(209,250,229,0.5);">{{ auth()->user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    @csrf
                    <button type="submit" class="transition-colors hover:text-white" style="color: rgba(209,250,229,0.4);" title="Sair">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

    <div class="flex-1 flex flex-col min-h-screen transition-all duration-300"
         :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"
         style="background-color: #f8fafc;">

        <x-impersonation-banner />

        @isset($header)
            <header class="sticky top-0 z-10 bg-white border-b flex-shrink-0 px-4 lg:px-6 py-3 flex items-center gap-3"
                    style="border-color: #e2e8f0;">
                <button @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 transition flex-shrink-0"
                        aria-label="Abrir menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1 min-w-0">{{ $header }}</div>
            </header>
        @endisset

        <main class="flex-1 px-4 py-4 lg:px-6 lg:py-5">
            {{ $slot }}
        </main>
    </div>
</div>

<x-toast-container />
<x-confirm-delete-modal />

</body>
</html>
