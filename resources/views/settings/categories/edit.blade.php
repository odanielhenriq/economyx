<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Editar categoria</h2>
            <a href="{{ route('categories.index') }}" class="text-sm text-indigo-600 hover:underline">
                Voltar
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8 space-y-6">
            @include('settings.nav')

            @if ($errors->any())
                <div class="p-3 text-sm text-red-800 bg-red-100 border border-red-200 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="p-6 bg-white rounded shadow-sm border space-y-4">
                    <div>
                        <label class="text-sm text-gray-600">Nome</label>
                        <input type="text" name="name" value="{{ old('name', $category->name) }}"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Slug (opcional)</label>
                        <input type="text" name="slug" value="{{ old('slug', $category->slug) }}"
                            class="mt-1 w-full rounded border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="text-sm text-gray-600">Cor (opcional)</label>
                        <input type="text" name="color" value="{{ old('color', $category->color) }}"
                            class="mt-1 w-full rounded border-gray-300 text-sm" placeholder="#22c55e">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-indigo-600 rounded hover:bg-indigo-700">
                        Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
