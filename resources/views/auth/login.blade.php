<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Entrar</h2>
        <p class="mt-1 text-sm text-slate-500">Bem-vindo de volta.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Senha" />
            <x-text-input id="password" type="password" name="password"
                required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                    class="rounded border-slate-300 text-green-600 focus:ring-green-500">
                <span class="text-sm text-slate-600">Lembrar de mim</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-green-700 hover:text-green-900 font-medium"
                   href="{{ route('password.request') }}">
                    Esqueceu a senha?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            Entrar
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-500">
                Não tem conta?
                <a href="{{ route('register') }}" class="font-medium text-green-700 hover:text-green-900">
                    Criar conta
                </a>
            </p>
        @endif
    </form>
</x-guest-layout>
