@props([
    'hasTransactions' => false,
    'hasCreditCards' => false,
    'hasBudgets' => false,
])

@if (! $hasTransactions || ! $hasCreditCards || ! $hasBudgets)
    <div
        x-data="{ dismissed: localStorage.getItem('economyx_checklist_dismissed') === '1', dismiss() { this.dismissed = true; localStorage.setItem('economyx_checklist_dismissed', '1'); } }"
        x-show="!dismissed"
        x-transition
        class="rounded-xl border border-green-200 bg-gradient-to-br from-green-50 to-white p-5 sm:p-6"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Comece sua organização financeira</h2>
                <p class="mt-1 text-sm text-slate-600">Complete os passos abaixo para aproveitar o Economyx.</p>
            </div>
            <button type="button" @click="dismiss()" class="text-slate-400 hover:text-slate-600 shrink-0" aria-label="Fechar checklist">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <ul class="mt-5 space-y-3">
            <li class="flex items-center gap-3 text-sm">
                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $hasTransactions ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-400' }}">
                    @if ($hasTransactions) ✓ @else 1 @endif
                </span>
                <span class="{{ $hasTransactions ? 'text-slate-500 line-through' : 'text-slate-800' }}">Cadastrar primeira transação</span>
                @unless ($hasTransactions)
                    <a href="{{ route('transactions.create') }}" class="ml-auto text-sm font-medium text-green-700 hover:text-green-900">Lançar →</a>
                @endunless
            </li>
            <li class="flex items-center gap-3 text-sm">
                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $hasCreditCards ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-400' }}">
                    @if ($hasCreditCards) ✓ @else 2 @endif
                </span>
                <span class="{{ $hasCreditCards ? 'text-slate-500 line-through' : 'text-slate-800' }}">Cadastrar cartão de crédito</span>
                @unless ($hasCreditCards)
                    <a href="{{ route('credit-cards.create') }}" class="ml-auto text-sm font-medium text-green-700 hover:text-green-900">Cadastrar →</a>
                @endunless
            </li>
            <li class="flex items-center gap-3 text-sm">
                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $hasBudgets ? 'bg-green-600 text-white' : 'bg-white border border-slate-200 text-slate-400' }}">
                    @if ($hasBudgets) ✓ @else 3 @endif
                </span>
                <span class="{{ $hasBudgets ? 'text-slate-500 line-through' : 'text-slate-800' }}">Definir orçamento por categoria</span>
                @unless ($hasBudgets)
                    <a href="{{ route('budgets.index') }}" class="ml-auto text-sm font-medium text-green-700 hover:text-green-900">Configurar →</a>
                @endunless
            </li>
        </ul>
    </div>
@endif
