<div
    x-data="{
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => this.remove(id), 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-on:toast.window="add($event.detail.message, $event.detail.type)"
    class="fixed bottom-4 right-4 z-50 space-y-2"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition
            class="flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg text-sm text-white"
            :class="{
                'bg-green-600': toast.type === 'success',
                'bg-red-600':   toast.type === 'error',
                'bg-amber-500': toast.type === 'warning'
            }"
        >
            <span x-text="toast.message"></span>
            <button @click="remove(toast.id)" class="ml-auto opacity-70 hover:opacity-100">✕</button>
        </div>
    </template>
</div>
