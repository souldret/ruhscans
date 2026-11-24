<button {{ $attributes->merge(['type' => 'button', 'class' => 'tap-target inline-flex items-center justify-center rounded-full border border-white/15 bg-white/5 px-6 py-3 text-base font-medium text-mist transition hover:border-magenta/50 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-magenta disabled:opacity-50']) }}>
    {{ $slot }}
</button>
