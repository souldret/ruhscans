<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tap-target inline-flex w-full items-center justify-center rounded-full bg-magenta px-6 py-3 text-base font-semibold text-ink transition hover:shadow-glow disabled:opacity-60']) }}>
    {{ $slot }}
</button>
