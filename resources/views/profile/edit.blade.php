<x-app-layout>
    <x-slot name="header">
        <x-page-header
            title="Meu perfil"
            subtitle="Atualize seu nome, e-mail e senha de acesso ao Economyx."
        />
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="p-5 sm:p-8 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-5 sm:p-8 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-5 sm:p-8 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
