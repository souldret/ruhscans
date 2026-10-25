<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full bg-accent px-5 py-2.5 text-sm font-semibold text-white shadow-glow transition duration-base ease-out hover:brightness-110 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-60 focus-visible:outline']) }}>
    {{ $slot }}
</button>
