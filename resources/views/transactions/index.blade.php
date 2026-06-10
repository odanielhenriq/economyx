{{-- resources/views/transactions/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Transações"
            subtitle="Consulte, filtre e gerencie todas as suas receitas e despesas."
        >
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2" x-data="exportModal()">
                {{-- Menu Exportar --}}
                <div class="relative" @click.outside="exportOpen = false">
                    <button type="button" @click="exportOpen = !exportOpen"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Exportar
                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div x-show="exportOpen" x-transition style="display:none"
                        class="absolute right-0 mt-1 w-56 rounded-lg border border-slate-200 bg-white py-1 shadow-lg z-20">
                        <button type="button" @click="open = true; exportOpen = false"
                            class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">
                            Exportar CSV
                        </button>
                        <div class="border-t border-slate-100 mx-2 my-1"></div>
                        <p class="px-4 pt-2 pb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Mais opções</p>
                        <a href="{{ route('export.json') }}" download="economyx-{{ now()->format('Y-m') }}.json" @click="exportOpen = false"
                            class="block px-4 py-2.5 text-left hover:bg-slate-50"
                            title="Backup completo dos seus dados financeiros">
                            <span class="block text-xs font-medium text-slate-500">Exportar JSON</span>
                            <span class="block text-[11px] text-slate-400 mt-0.5">Avançado — backup ou integração externa</span>
                        </a>
                    </div>
                </div>

                <button type="button" @click="window.dispatchEvent(new CustomEvent('open-import'))"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    Importar
                </button>

                <a href="{{ route('transactions.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Nova transação
                </a>
            </div>

            {{-- Modal de exportação CSV --}}
            <div
                x-show="open"
                x-on:keydown.escape.window="open = false"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display:none">
                <div class="absolute inset-0 bg-black/50" @click="open = false"></div>
                <div class="relative bg-white rounded-xl shadow-sm border border-slate-200 w-full max-w-md p-6" @click.stop>

                    <h2 class="text-base font-semibold text-slate-900 mb-1">Exportar transações</h2>
                    <p class="text-sm text-slate-500 mb-5">Escolha o período e baixe um arquivo CSV.</p>

                    {{-- Presets --}}
                    <div class="flex flex-wrap gap-2 mb-5">
                        <template x-for="preset in presets" :key="preset.label">
                            <button
                                type="button"
                                @click="applyPreset(preset)"
                                :class="startDate === preset.start && endDate === preset.end
                                    ? 'bg-green-600 text-white border-green-600'
                                    : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition"
                                x-text="preset.label">
                            </button>
                        </template>
                    </div>

                    {{-- Inputs de data --}}
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">De</label>
                            <input type="date" x-model="startDate"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Até</label>
                            <input type="date" x-model="endDate"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500" />
                        </div>
                    </div>

                    {{-- Ações --}}
                    <div class="flex justify-end gap-3">
                        <button type="button"
                                @click="open = false"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="button"
                                @click="exportar()"
                                :disabled="!startDate || !endDate || loading"
                                :class="(!startDate || !endDate || loading) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-700'"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg transition">
                            <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            <span x-text="loading ? 'Gerando...' : 'Baixar CSV'"></span>
                        </button>
                    </div>
                </div>
            </div>
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">

        <x-flash-messages />

        {{-- Filtros --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex items-center justify-between gap-3 mb-1">
                <h2 class="text-sm font-semibold text-slate-700">Filtros</h2>
                <span id="filter-active-badge" class="hidden text-xs font-medium text-green-700 bg-green-50 px-2 py-1 rounded-full"></span>
            </div>
            <p class="text-xs text-slate-400 mb-4">Refine a lista por mês, categoria ou pessoa. Clique em <strong class="font-medium text-slate-600">Filtrar</strong> para aplicar ou <strong class="font-medium text-slate-600">Limpar</strong> para ver tudo.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <div class="md:w-52">
                    <label for="filter-search" class="block text-xs font-medium text-slate-500 mb-1">Pesquisar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" id="filter-search"
                            placeholder="Buscar por descrição..."
                            class="w-full pl-9 pr-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent placeholder:text-slate-400">
                    </div>
                </div>

                <div>
                    <label for="filter-month" class="block text-xs font-medium text-slate-500 mb-1">Mês</label>
                    <div class="relative">
                        <input type="month" id="filter-month" title="Selecione o mês"
                            class="month-empty w-full min-w-[10.5rem] px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent [color-scheme:light]">
                        <span id="filter-month-placeholder"
                            class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-sm text-slate-400">
                            Selecione o mês
                        </span>
                    </div>
                </div>

                <div>
                    <label for="filter-user" class="block text-xs font-medium text-slate-500 mb-1">Pessoa</label>
                    <select id="filter-user"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-category" class="block text-xs font-medium text-slate-500 mb-1">Categoria</label>
                    <select id="filter-category"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-type" class="block text-xs font-medium text-slate-500 mb-1">Tipo</label>
                    <select id="filter-type"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todos</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter-payment-method" class="block text-xs font-medium text-slate-500 mb-1">Pagamento</label>
                    <select id="filter-payment-method"
                        class="px-3 py-2 text-sm text-slate-900 bg-white border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Todas</option>
                        @foreach ($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-full">
                    <button id="filter-apply"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Filtrar
                    </button>
                    <button id="filter-clear"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                        Limpar
                    </button>
                </div>

            </div>
        </div>

        {{-- Wrapper com loading skeleton + conteúdo real --}}
        <div id="transactions-table-wrapper" x-data="{ loading: true }">

            {{-- Skeleton --}}
            <div x-show="loading" class="space-y-3">
                {{-- Skeleton dos cards de resumo --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-2">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="h-3 w-28 bg-slate-200 rounded animate-pulse mb-3"></div>
                        <div class="h-7 w-36 bg-slate-200 rounded animate-pulse"></div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="h-3 w-28 bg-slate-200 rounded animate-pulse mb-3"></div>
                        <div class="h-7 w-36 bg-slate-200 rounded animate-pulse"></div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="h-3 w-28 bg-slate-200 rounded animate-pulse mb-3"></div>
                        <div class="h-7 w-36 bg-slate-200 rounded animate-pulse"></div>
                    </div>
                </div>
                {{-- Skeleton das linhas da tabela --}}
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
                <div class="h-10 bg-slate-200 rounded-xl animate-pulse"></div>
            </div>

            {{-- Conteúdo real --}}
            <div x-show="!loading" style="display:none" class="space-y-4">

                <div id="transactions-state" class=""></div>

                <div id="transactions-data" class="space-y-4">
                {{-- Cards de resumo --}}
                <div id="transactions-summary" class="grid grid-cols-1 gap-4 md:grid-cols-3"></div>

                {{-- Tabela (desktop) --}}
                <div class="hidden md:block bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left divide-y divide-slate-100 min-w-[640px]">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Categoria</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Data</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Descrição</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Valor</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Minha parte</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Pessoas</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Parcelas</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-body" class="divide-y divide-slate-100"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Cards (mobile) --}}
                <div id="transactions-mobile" class="md:hidden space-y-3"></div>

                {{-- Paginação --}}
                <div id="transactions-pagination" class="flex items-center justify-between text-sm text-slate-500">
                    <button id="prev-page"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed"
                        disabled>
                        ← Anterior
                    </button>
                    <span id="pagination-info" class="text-xs text-slate-500">Página 1 de 1</span>
                    <button id="next-page"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium rounded-lg border border-slate-200 transition disabled:opacity-40 disabled:cursor-not-allowed">
                        Próxima →
                    </button>
                </div>
                </div>

            </div>
        </div>

    </div>

    {{-- FAB mobile — botão flutuante de nova transação --}}
    <a href="{{ route('transactions.create') }}"
       class="lg:hidden fixed bottom-6 right-6 z-50
              w-14 h-14 bg-green-600 hover:bg-green-700
              rounded-full shadow-lg flex items-center justify-center
              text-white transition active:scale-95">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 4v16m8-8H4" />
        </svg>
    </a>

    <script type="module">
        const stateEl = document.getElementById('transactions-state');
        const bodyEl = document.getElementById('transactions-body');
        const mobileEl = document.getElementById('transactions-mobile');
        const prevBtn = document.getElementById('prev-page');
        const nextBtn = document.getElementById('next-page');
        const paginationInfoEl = document.getElementById('pagination-info');
        const summaryEl = document.getElementById('transactions-summary');
        const dataEl = document.getElementById('transactions-data');
        const filterBadgeEl = document.getElementById('filter-active-badge');

        const filterSearchEl = document.getElementById('filter-search');
        const filterMonthEl = document.getElementById('filter-month');
        const filterMonthPlaceholder = document.getElementById('filter-month-placeholder');
        const filterUserEl = document.getElementById('filter-user');
        const filterCategoryEl = document.getElementById('filter-category');
        const filterTypeEl = document.getElementById('filter-type');
        const filterPaymentMethodEl = document.getElementById('filter-payment-method');
        const filterApplyBtn = document.getElementById('filter-apply');
        const filterClearBtn = document.getElementById('filter-clear');

        let currentPage = 1;
        const perPage = 10;
        let currentFilters = {
            search: '',
            month: '',
            user_id: '',
            category_id: '',
            type_id: '',
            payment_method_id: '',
        };

        function syncFiltersToUrl(filters) {
            const params = new URLSearchParams();
            if (filters.search)            params.set('search', filters.search);
            if (filters.month)             params.set('month', filters.month);
            if (filters.user_id)           params.set('user_id', filters.user_id);
            if (filters.category_id)       params.set('category_id', filters.category_id);
            if (filters.type_id)           params.set('type_id', filters.type_id);
            if (filters.payment_method_id) params.set('payment_method_id', filters.payment_method_id);

            const newUrl = params.toString()
                ? `${window.location.pathname}?${params.toString()}`
                : window.location.pathname;

            window.history.replaceState({}, '', newUrl);
        }

        function syncMonthPlaceholder() {
            const hasValue = !!filterMonthEl.value;
            filterMonthEl.classList.toggle('text-transparent', !hasValue);
            filterMonthPlaceholder?.classList.toggle('hidden', hasValue);
        }

        function initFiltersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('search'))            { currentFilters.search = params.get('search'); filterSearchEl.value = currentFilters.search; }
            if (params.get('month'))             { currentFilters.month = params.get('month'); filterMonthEl.value = currentFilters.month; }
            if (params.get('user_id'))           { currentFilters.user_id = params.get('user_id'); filterUserEl.value = currentFilters.user_id; }
            if (params.get('category_id'))       { currentFilters.category_id = params.get('category_id'); filterCategoryEl.value = currentFilters.category_id; }
            if (params.get('type_id'))           { currentFilters.type_id = params.get('type_id'); filterTypeEl.value = currentFilters.type_id; }
            if (params.get('payment_method_id')) { currentFilters.payment_method_id = params.get('payment_method_id'); filterPaymentMethodEl.value = currentFilters.payment_method_id; }
            syncMonthPlaceholder();
        }

        function updateFilterBadge(filters) {
            const count = Object.values(filters).filter(Boolean).length;
            if (!filterBadgeEl) return;
            if (count === 0) {
                filterBadgeEl.classList.add('hidden');
                return;
            }
            filterBadgeEl.textContent = count === 1 ? '1 filtro ativo' : `${count} filtros ativos`;
            filterBadgeEl.classList.remove('hidden');
        }

        function hasActiveFilters(filters) {
            return Object.values(filters).some(Boolean);
        }

        function buildEmptyStateHtml(filters) {
            const filtered = hasActiveFilters(filters);
            const title = filtered
                ? 'Nenhum resultado para os filtros escolhidos'
                : 'Você ainda não tem transações';
            const description = filtered
                ? 'Tente outro mês, categoria ou termo de busca — ou limpe os filtros para ver tudo.'
                : 'Adicione uma receita, despesa ou compra parcelada para começar a acompanhar suas finanças.';
            const showClear = filtered;

            return `
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-6 py-14 text-center">
                    <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">${title}</h3>
                    <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">${description}</p>
                    <div class="mt-6 flex flex-col sm:flex-row items-center justify-center gap-3">
                        <a href="{{ route('transactions.create') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition">
                            + Nova transação
                        </a>
                        ${showClear ? `
                        <button type="button" id="empty-clear-filters"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                            Limpar filtros
                        </button>` : ''}
                    </div>
                </div>
            `;
        }

        function buildErrorStateHtml() {
            return `
                <div class="bg-white rounded-xl border border-red-200 shadow-sm px-6 py-10 text-center">
                    <p class="text-sm font-medium text-slate-900">Não foi possível carregar as transações</p>
                    <p class="mt-1 text-sm text-slate-500">Verifique sua conexão e tente novamente.</p>
                    <button type="button" id="transactions-retry-btn"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition">
                        Tentar novamente
                    </button>
                </div>
            `;
        }

        function buildActionsHtml(txId) {
            return `
                <a href="/transactions/${txId}/edit"
                   class="inline-flex items-center p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                   title="Editar">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                    </svg>
                </a>
                <button data-id="${txId}"
                    class="duplicate-btn inline-flex items-center p-1.5 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition"
                    title="Duplicar transação">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 4h8a2 2 0 012 2v8a2 2 0 01-2 2H10a2 2 0 01-2-2v-8a2 2 0 012-2z"/>
                    </svg>
                </button>
                <button data-id="${txId}"
                    class="delete-btn inline-flex items-center p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                    title="Excluir">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </button>`;
        }

        function setTableLoading(value) {
            const wrapper = document.getElementById('transactions-table-wrapper');
            if (!wrapper || typeof Alpine === 'undefined') return;
            const data = Alpine.$data(wrapper);
            if (data) data.loading = value;
        }

        async function loadTransactions(page = 1, filters = {}) {
            setTableLoading(true);

            try {
                const params = new URLSearchParams();
                params.set('per_page', perPage);
                params.set('page', page);

                if (filters.search)            params.set('search', filters.search);
                if (filters.month)             params.set('month', filters.month);
                if (filters.user_id)           params.set('user_id', filters.user_id);
                if (filters.category_id)       params.set('category_id', filters.category_id);
                if (filters.type_id)           params.set('type_id', filters.type_id);
                if (filters.payment_method_id) params.set('payment_method_id', filters.payment_method_id);

                const response = await fetch(`/api/transactions?${params.toString()}`);
                if (!response.ok) throw new Error('Erro ao carregar transações');

                const json = await response.json();
                const items = json.data ?? [];
                const meta = json.meta ?? null;
                const links = json.links ?? null;

                bodyEl.innerHTML = '';
                if (mobileEl) mobileEl.innerHTML = '';
                summaryEl.innerHTML = '';

                if (items.length === 0) {
                    if (dataEl) dataEl.classList.add('hidden');
                    stateEl.innerHTML = buildEmptyStateHtml(filters);
                    document.getElementById('empty-clear-filters')?.addEventListener('click', () => filterClearBtn.click());
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                    updateFilterBadge(filters);
                    return;
                }

                if (dataEl) dataEl.classList.remove('hidden');
                stateEl.innerHTML = '';

                // Totais do período completo (vindos do meta da API, não da página atual)
                const periodTotals = meta?.totals ?? {};
                const totalIncome   = periodTotals.income  ?? 0;
                const totalExpense  = Math.abs(periodTotals.expense ?? 0);
                const balance       = periodTotals.balance ?? 0;

                summaryEl.innerHTML = `
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Receitas do período</div>
                        <div class="text-xl font-bold text-emerald-700 tabular-nums">
                            ${totalIncome.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Despesas do período</div>
                        <div class="text-xl font-bold text-red-600 tabular-nums">
                            ${totalExpense.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <div class="text-xs font-medium text-slate-500 mb-1">Saldo do período</div>
                        <div class="text-xl font-bold tabular-nums ${balance >= 0 ? 'text-emerald-700' : 'text-red-600'}">
                            ${balance.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}
                        </div>
                    </div>
                `;

                // Tabela
                items.forEach(tx => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50';

                    const isNegative = Number(tx.signed_amount) < 0;
                    const amountFormatted = Number(tx.signed_amount).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const totalAmount = tx.total_amount ?? null;
                    const totalFormatted = totalAmount !== null
                        ? Number(totalAmount).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                        : null;
                    const perUserFormatted = tx.totals?.per_user_share != null
                        ? Number(tx.totals.per_user_share).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                        : '-';

                    const ownerLabel = tx.credit_card?.owner_label || tx.credit_card?.owner_name || '';
                    const infoBadges = `
                        <span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-slate-100 text-slate-600">
                            ${tx.category?.name ?? '-'}
                        </span>
                        ${
                            tx.payment_method?.name === 'Credit Card' && tx.credit_card?.name
                                ? `<span class="inline-flex items-center px-2 py-0.5 text-[11px] rounded-full bg-purple-100 text-purple-700">
                                    ${tx.credit_card.name}${ownerLabel ? ' (' + ownerLabel + ')' : ''}
                                   </span>`
                                : ''
                        }
                    `;

                    const usersDetailHtml = (tx.users ?? [])
                        .map(u => {
                            const share = Number(u.share_amount ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                            return `<div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="text-slate-700">${u.name}</span>
                                        <span class="text-slate-400 tabular-nums">${share}</span>
                                    </div>`;
                        })
                        .join('');

                    const installmentLabel = tx.installments?.is_installment
                        ? `<button
                              data-installment-id="${tx.id}"
                              class="installment-btn inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs
                                     font-medium bg-purple-100 text-purple-700 hover:bg-purple-200 transition cursor-pointer"
                              title="Ver todas as parcelas">
                              ${tx.installments.label}
                           </button>
                           <div class="text-xs text-slate-400 mt-0.5">faltam ${tx.installments.remaining}</div>`
                        : '-';

                    tr.innerHTML = `
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-wrap gap-1">${infoBadges}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-500 align-top whitespace-nowrap">${tx.date}</td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center gap-1.5">
                                <span class="font-medium text-slate-800">${tx.description ?? '(sem descrição)'}</span>
                                ${tx.recurring_transaction_id ? '<span class="text-xs text-slate-400" title="Conta fixa">🔁</span>' : ''}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right align-top">
                            <span class="font-semibold tabular-nums ${isNegative ? 'text-red-600' : 'text-emerald-700'}">
                                ${amountFormatted}
                            </span>
                            ${totalFormatted && tx.installments?.is_installment
                                ? `<div class="text-xs text-slate-400 tabular-nums">Total: ${totalFormatted}</div>`
                                : ''}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600 tabular-nums align-top">${perUserFormatted}</td>
                        <td class="px-4 py-3 align-top">${usersDetailHtml || '<span class="text-slate-400">-</span>'}</td>
                        <td class="px-4 py-3 text-slate-500 align-top text-xs">${installmentLabel}</td>
                        <td class="px-4 py-3 text-center align-top">
                            <div class="flex items-center justify-center gap-1">
                                ${buildActionsHtml(tx.id)}
                            </div>
                        </td>
                    `;

                    bodyEl.appendChild(tr);

                    if (mobileEl) {
                        const card = document.createElement('div');
                        card.className = 'bg-white rounded-xl border border-slate-200 shadow-sm p-4';
                        card.innerHTML = `
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-medium text-slate-900 truncate">${tx.description ?? '(sem descrição)'}</p>
                                        ${tx.recurring_transaction_id ? '<span class="text-xs text-slate-400 shrink-0" title="Conta fixa">🔁</span>' : ''}
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">${tx.date}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-semibold tabular-nums text-sm ${isNegative ? 'text-red-600' : 'text-emerald-700'}">${amountFormatted}</span>
                                    ${totalFormatted && tx.installments?.is_installment
                                        ? `<div class="text-xs text-slate-400 tabular-nums">Total: ${totalFormatted}</div>`
                                        : ''}
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1 mb-2">${infoBadges}</div>
                            ${perUserFormatted !== '-'
                                ? `<div class="text-xs text-slate-500 mb-2">Minha parte: <span class="font-medium text-slate-700 tabular-nums">${perUserFormatted}</span></div>`
                                : ''}
                            ${usersDetailHtml ? `<div class="mb-2 space-y-1">${usersDetailHtml}</div>` : ''}
                            ${tx.installments?.is_installment ? `<div class="mb-3">${installmentLabel}</div>` : ''}
                            <div class="flex items-center justify-end gap-1 pt-2 border-t border-slate-100">
                                ${buildActionsHtml(tx.id)}
                            </div>
                        `;
                        mobileEl.appendChild(card);
                    }
                });

                // Paginação
                if (meta) {
                    currentPage = meta.current_page;
                    paginationInfoEl.textContent = `Página ${meta.current_page} de ${meta.last_page}`;
                    prevBtn.disabled = !(links && links.prev);
                    nextBtn.disabled = !(links && links.next);
                } else {
                    paginationInfoEl.textContent = '';
                    prevBtn.disabled = true;
                    nextBtn.disabled = true;
                }

                syncFiltersToUrl(filters);
                updateFilterBadge(filters);

            } catch (error) {
                console.error(error);
                if (dataEl) dataEl.classList.add('hidden');
                stateEl.innerHTML = buildErrorStateHtml();
                document.getElementById('transactions-retry-btn')?.addEventListener('click', () => loadTransactions(currentPage, currentFilters));
                bodyEl.innerHTML = '';
                if (mobileEl) mobileEl.innerHTML = '';
                summaryEl.innerHTML = '';
                paginationInfoEl.textContent = '';
                prevBtn.disabled = true;
                nextBtn.disabled = true;
            } finally {
                setTableLoading(false);
            }
        }

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) loadTransactions(currentPage - 1, currentFilters);
        });

        nextBtn.addEventListener('click', () => {
            loadTransactions(currentPage + 1, currentFilters);
        });

        filterApplyBtn.addEventListener('click', () => {
            currentFilters = {
                search: filterSearchEl.value || '',
                month: filterMonthEl.value || '',
                user_id: filterUserEl.value || '',
                category_id: filterCategoryEl.value || '',
                type_id: filterTypeEl.value || '',
                payment_method_id: filterPaymentMethodEl.value || '',
            };
            loadTransactions(1, currentFilters);
        });

        filterClearBtn.addEventListener('click', () => {
            filterSearchEl.value = '';
            filterMonthEl.value = '';
            syncMonthPlaceholder();
            filterUserEl.value = '';
            filterCategoryEl.value = '';
            filterTypeEl.value = '';
            filterPaymentMethodEl.value = '';
            currentFilters = { search: '', month: '', user_id: '', category_id: '', type_id: '', payment_method_id: '' };
            updateFilterBadge(currentFilters);
            loadTransactions(1, currentFilters);
        });

        document.addEventListener('click', async (e) => {
            const dupBtn = e.target.closest('.duplicate-btn');
            if (dupBtn) {
                const id = dupBtn.dataset.id;
                try {
                    const res = await apiFetch(`/api/transactions/${id}/duplicate`, { method: 'POST' });
                    if (!res.ok) throw new Error();
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Transação duplicada! Aparece com data de hoje.', type: 'success' }
                    }));
                    await loadTransactions(currentPage, currentFilters);
                } catch {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: 'Não foi possível duplicar a transação. Tente novamente.', type: 'error' }
                    }));
                }
                return;
            }
        });

        document.addEventListener('click', (e) => {
            const installBtn = e.target.closest('.installment-btn');
            if (installBtn) {
                const id = installBtn.dataset.installmentId;
                window.dispatchEvent(new CustomEvent('open-installments', { detail: { id } }));
                return;
            }
        });

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.delete-btn');
            if (!btn) return;

            const id = btn.dataset.id;
            window.dispatchEvent(new CustomEvent('request-delete', {
                detail: {
                    title: 'Excluir transação?',
                    message: 'Essa ação não pode ser desfeita. A transação será removida permanentemente.',
                    confirmLabel: 'Excluir transação',
                    callback: async () => {
                        try {
                            const response = await apiFetch(`/api/transactions/${id}`, { method: 'DELETE' });

                            if (!response.ok) {
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: { message: 'Não foi possível excluir a transação. Tente novamente.', type: 'error' }
                                }));
                                return;
                            }

                            await loadTransactions(currentPage, currentFilters);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Transação excluída.', type: 'success' }
                            }));
                        } catch (err) {
                            console.error(err);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: { message: 'Erro inesperado ao excluir.', type: 'error' }
                            }));
                        }
                    }
                }
            }));
        });

        function bootTransactionsList() {
            initFiltersFromUrl();
            updateFilterBadge(currentFilters);
            loadTransactions(1, currentFilters);
        }

        filterMonthEl.addEventListener('change', syncMonthPlaceholder);
        filterMonthEl.addEventListener('input', syncMonthPlaceholder);

        if (typeof Alpine !== 'undefined') {
            bootTransactionsList();
        } else {
            document.addEventListener('alpine:init', bootTransactionsList, { once: true });
        }
    </script>

    {{-- Modal de parcelas --}}
    <div
        x-data="{
            open: false,
            loading: false,
            description: '',
            installments: [],
            init() {
                window.addEventListener('open-installments', async (e) => {
                    this.open = true;
                    this.loading = true;
                    this.installments = [];
                    this.description = '';
                    try {
                        const res = await fetch('/api/transactions/' + e.detail.id + '/installments');
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        const data = await res.json();
                        this.description  = data.description ?? '';
                        this.installments = Array.isArray(data.installments) ? data.installments : [];
                    } catch {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: 'Não foi possível carregar as parcelas. Tente novamente.', type: 'error' }
                        }));
                        this.open = false;
                    } finally {
                        this.loading = false;
                    }
                });
            }
        }"
        x-show="open"
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display:none">

        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        <div class="relative bg-white rounded-xl border border-slate-200 shadow-sm w-full max-w-lg p-6" @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-semibold text-slate-900" x-text="description"></h2>
                    <p class="text-sm text-slate-500 mt-0.5" x-text="installments.length + ' parcelas no total'"></p>
                </div>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Skeleton --}}
            <div x-show="loading" class="space-y-3">
                <template x-for="i in 4">
                    <div class="h-12 bg-slate-100 rounded-lg animate-pulse"></div>
                </template>
            </div>

            {{-- Lista de parcelas --}}
            <div x-show="!loading" class="space-y-2 max-h-96 overflow-y-auto pr-1">
                <template x-for="inst in installments" :key="inst.id">
                    <div
                        :class="{
                            'bg-green-50 border-green-200': inst.is_past && !inst.is_current,
                            'bg-indigo-50 border-indigo-300 ring-1 ring-indigo-300': inst.is_current,
                            'bg-white border-slate-200': !inst.is_past && !inst.is_current,
                        }"
                        class="flex items-center justify-between px-4 py-3 rounded-lg border transition">

                        <div class="flex items-center gap-3">
                            <div
                                :class="{
                                    'bg-green-500': inst.is_past && !inst.is_current,
                                    'bg-indigo-500': inst.is_current,
                                    'bg-slate-200': !inst.is_past && !inst.is_current,
                                }"
                                class="w-2 h-2 rounded-full flex-shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium text-slate-900"
                                   x-text="'Parcela ' + inst.installment_number + ' de ' + inst.total"></p>
                                <p class="text-xs text-slate-400"
                                   x-text="inst.due_date
                                       ? new Date(inst.due_date + 'T12:00:00').toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
                                       : '-'"></p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-semibold tabular-nums"
                               :class="{
                                   'text-green-700': inst.is_past && !inst.is_current,
                                   'text-indigo-700': inst.is_current,
                                   'text-slate-500': !inst.is_past && !inst.is_current,
                               }"
                               x-text="'R$ ' + Number(inst.amount).toLocaleString('pt-BR', { minimumFractionDigits: 2 })"></p>
                            <p class="text-xs mt-0.5"
                               :class="{
                                   'text-green-500': inst.is_past && !inst.is_current,
                                   'text-indigo-500': inst.is_current,
                                   'text-slate-400': !inst.is_past && !inst.is_current,
                               }"
                               x-text="inst.is_current ? 'Mês atual' : inst.is_past ? 'Pago' : 'Futuro'"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function exportModal() {
            return {
                open: false,
                exportOpen: false,
                startDate: '',
                endDate: '',
                loading: false,
                presets: [
                    {
                        label: 'Este mês',
                        start: '{{ now()->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->endOfMonth()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Mês passado',
                        start: '{{ now()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Últimos 3 meses',
                        start: '{{ now()->subMonths(3)->startOfMonth()->format('Y-m-d') }}',
                        end:   '{{ now()->format('Y-m-d') }}',
                    },
                    {
                        label: 'Este ano',
                        start: '{{ now()->startOfYear()->format('Y-m-d') }}',
                        end:   '{{ now()->format('Y-m-d') }}',
                    },
                ],
                applyPreset(preset) {
                    this.startDate = preset.start;
                    this.endDate   = preset.end;
                },
                exportar() {
                    if (!this.startDate || !this.endDate) return;
                    this.loading = true;
                    const url = '{{ route('transactions.export') }}'
                        + '?start_date=' + this.startDate
                        + '&end_date='   + this.endDate;
                    window.location.href = url;
                    setTimeout(() => { this.loading = false; }, 1500);
                },
            };
        }
    </script>

    @include('transactions._import_modal')

</x-app-layout>
