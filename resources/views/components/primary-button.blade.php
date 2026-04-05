<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
