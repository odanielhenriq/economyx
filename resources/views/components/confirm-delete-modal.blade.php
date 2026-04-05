<div
    x-data="{ open: false, action: null }"
    x-on:request-delete.window="open = true; action = $event.detail"
>
    <div
        x-show="open"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50"
        @click.self="open = false; action = null"
    >
        <div class="bg-white w-full max-w-sm p-6 rounded-xl shadow-sm border border-slate-200">
            <h2 class="text-lg font-semibold text-slate-800 mb-3">Confirmar exclusão</h2>
            <p class="text-sm text-slate-600 mb-6">
                Tem certeza que deseja excluir este item? Esta ação não poderá ser desfeita.
            </p>
            <div class="flex justify-end gap-3">
                <button
                    @click="open = false; action = null"
                    class="px-4 py-2 text-sm text-slate-700 rounded-lg border border-slate-200 hover:bg-slate-50"
                >
                    Cancelar
                </button>
                <button
                    @click="
                        if (action && action.form) { action.form.submit(); }
                        else if (action && action.callback) { action.callback(); }
                        open = false;
                        action = null;
                    "
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700"
                >
                    Excluir
                </button>
            </div>
        </div>
    </div>
</div>
