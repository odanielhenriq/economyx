<div class="flex flex-wrap gap-2 text-sm">
    <a href="{{ route('categories.index') }}"
        class="px-3 py-1 rounded border {{ request()->routeIs('categories.*') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700' }}">
        Categorias
    </a>
    <a href="{{ route('types.index') }}"
        class="px-3 py-1 rounded border {{ request()->routeIs('types.*') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700' }}">
        Tipos
    </a>
    <a href="{{ route('payment-methods.index') }}"
        class="px-3 py-1 rounded border {{ request()->routeIs('payment-methods.*') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700' }}">
        Formas de pagamento
    </a>
    <a href="{{ route('credit-cards.index') }}"
        class="px-3 py-1 rounded border {{ request()->routeIs('credit-cards.*') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700' }}">
        Cartoes
    </a>
    <a href="{{ route('recurring-templates.index') }}"
        class="px-3 py-1 rounded border {{ request()->routeIs('recurring-templates.*') ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700' }}">
        Recorrencias
    </a>
</div>
