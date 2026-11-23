<x-night-layout :seo="['title' => 'Hesap ve parola']">
    <div class="mx-auto max-w-2xl space-y-6 px-4 pb-16 pt-8">
        <header>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-gold">Hesap</p>
            <h1 class="mt-2 font-display text-4xl text-mist">Hesap ve parola</h1>
            <p class="mt-2 text-base text-mist/70">Profil bilgilerini ve şifreni buradan güncelleyebilirsin.</p>
        </header>

        <div class="glass rounded-3xl p-6 sm:p-8">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="glass rounded-3xl p-6 sm:p-8">
            <livewire:profile.update-password-form />
        </div>

        <div class="glass rounded-3xl p-6 sm:p-8">
            <livewire:profile.delete-user-form />
        </div>
    </div>
</x-night-layout>
