    {{-- Modal de confirmação de exclusão de transação. --}}
     {{-- Ele é incluído via @include('transactions.modals.delete') na index.--}}
<div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden">
    <div class="bg-white w-full max-w-sm p-6 rounded shadow-lg">

        <h2 class="text-lg font-semibold text-gray-800 mb-3">
            Confirmar exclusão
        </h2>

        <p class="text-sm text-gray-600 mb-6">
            Tem certeza que deseja excluir esta transação? Esta ação não poderá ser desfeita.
        </p>

        <div class="flex justify-end gap-3">
            {{-- fecha modal sem excluir --}}
            <button id="cancel-delete" class="px-4 py-2 text-sm rounded border hover:bg-gray-100">
                Cancelar
            </button>

            {{-- botão que dispara o DELETE na API (via JS) --}}
            <button id="confirm-delete" class="px-4 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                Excluir
            </button>
        </div>

    </div>
</div>
