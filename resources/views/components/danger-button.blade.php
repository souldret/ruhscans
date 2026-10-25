<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-full border border-danger/40 bg-danger/10 px-5 py-2.5 text-sm font-semibold text-danger transition duration-base ease-out hover:bg-danger/20 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-60 focus-visible:outline']) }}>
    {{ $slot }}
</button>
