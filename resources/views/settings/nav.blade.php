@php
    $linkClass = fn (bool $active) => $active
        ? 'px-3 py-1.5 rounded-lg text-sm font-medium bg-green-600 text-white'
        : 'px-3 py-1.5 rounded-lg text-sm font-medium bg-white text-slate-600 border border-slate-200 hover:bg-slate-50';
@endphp

<nav class="flex flex-wrap gap-2 pb-2 border-b border-slate-100 mb-4" aria-label="Configurações">
    <a href="{{ route('partners.index') }}" class="{{ $linkClass(request()->routeIs('partners.*')) }}">Parceiros</a>
    <a href="{{ route('categories.index') }}" class="{{ $linkClass(request()->routeIs('categories.*')) }}">Categorias</a>
    <a href="{{ route('types.index') }}" class="{{ $linkClass(request()->routeIs('types.*')) }}">Tipos</a>
    <a href="{{ route('payment-methods.index') }}" class="{{ $linkClass(request()->routeIs('payment-methods.*')) }}">Formas de pagamento</a>
    <a href="{{ route('credit-cards.index') }}" class="{{ $linkClass(request()->routeIs('credit-cards.*')) }}">Meus cartões</a>
    <a href="{{ route('recurring-templates.index') }}" class="{{ $linkClass(request()->routeIs('recurring-templates.*')) }}">Contas fixas</a>
    <a href="{{ route('budgets.index') }}" class="{{ $linkClass(request()->routeIs('budgets.*')) }}">Orçamentos</a>
</nav>
