<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-slate-900">
            Excluir conta
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Ao excluir sua conta, todos os dados serão removidos permanentemente. Se quiser guardar algo, exporte antes em Transações → Exportar.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Excluir minha conta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-slate-900">
                Tem certeza que deseja excluir sua conta?
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Esta ação não pode ser desfeita. Digite sua senha para confirmar a exclusão permanente.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Senha" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Sua senha"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Excluir conta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
