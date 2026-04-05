<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Criar conta</h2>
        <p class="mt-1 text-sm text-slate-500">Comece a controlar suas finanças hoje.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input id="name" type="text" name="name" :value="old('name')"
                required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Senha" />
            <x-text-input id="password" type="password" name="password"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar senha" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation"
                required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full justify-center">
            Criar conta
        </x-primary-button>

        <p class="text-center text-sm text-slate-500">
            Já tem uma conta?
            <a href="{{ route('login') }}" class="font-medium text-green-700 hover:text-green-900">
                Entrar
            </a>
        </p>
    </form>
</x-guest-layout>
