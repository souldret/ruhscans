<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-ink-line bg-white/[0.04] px-5 py-2.5 text-sm font-semibold text-ink-text transition duration-base ease-out hover:border-ink-line-strong hover:bg-white/[0.08] active:scale-[0.98] disabled:pointer-events-none disabled:opacity-60 focus-visible:outline']) }}>
    {{ $slot }}
</button>
