<div
    x-data="{
        open: false,
        action: null,
        loading: false,
        get title() { return this.action?.title ?? 'Confirmar exclusão'; },
        get message() {
            if (this.action?.message) return this.action.message;
            return 'Tem certeza que deseja continuar? Esta ação não pode ser desfeita.';
        },
        get itemName() { return this.action?.itemName ?? null; },
        get confirmLabel() { return this.action?.confirmLabel ?? 'Excluir'; },
        close() {
            if (this.loading) return;
            this.open = false;
            this.action = null;
        },
        async confirm() {
            if (!this.action || this.loading) return;
            this.loading = true;
            try {
                if (this.action.form) {
                    this.action.form.submit();
                } else if (this.action.callback) {
                    await this.action.callback();
                }
            } finally {
                this.loading = false;
                this.open = false;
                this.action = null;
            }
        }
    }"
    x-on:request-delete.window="open = true; action = $event.detail; loading = false"
    x-on:keydown.escape.window="close()"
>
    <div
        x-show="open"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-black/50"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-delete-title"
        @click.self="close()"
    >
        <div
            x-show="open"
            x-transition
            class="bg-white w-full max-w-md p-6 rounded-xl shadow-lg border border-slate-200"
            @click.stop
        >
            <div class="flex items-start gap-3 mb-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h2 id="confirm-delete-title" class="text-lg font-semibold text-slate-900" x-text="title"></h2>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed" x-text="message"></p>
                    <p x-show="itemName" class="mt-2 text-sm font-medium text-slate-800 truncate" x-text="itemName ? '“' + itemName + '”' : ''"></p>
                </div>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                <button
                    type="button"
                    @click="close()"
                    :disabled="loading"
                    class="px-4 py-2.5 text-sm font-medium text-slate-700 rounded-lg border border-slate-200 hover:bg-slate-50 disabled:opacity-50"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="confirm()"
                    :disabled="loading"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                >
                    <svg x-show="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span x-text="loading ? 'Excluindo...' : confirmLabel"></span>
                </button>
            </div>
        </div>
    </div>
</div>
