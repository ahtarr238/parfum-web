<x-app-layout>
    <x-slot name="title">Profil</x-slot>
    <div class="page max-w-3xl pt-10">
        @include('partials.account-nav')

        <h1 class="mt-8 font-serif text-4xl">Profil</h1>

        <div class="mt-8 space-y-6">
            <div class="card p-6 sm:p-8">
                <livewire:profile.update-profile-information-form />
            </div>
            <div class="card p-6 sm:p-8">
                <livewire:profile.update-password-form />
            </div>
            <div class="card p-6 sm:p-8">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </div>
</x-app-layout>
