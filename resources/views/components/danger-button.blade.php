<button {{ $attributes->merge(['type' => 'submit', 'class' => 'tap-target inline-flex items-center justify-center rounded-full border border-[#FF2E88]/40 bg-[#9E1438] px-6 py-3 text-base font-semibold text-mist transition hover:bg-[#B81942] hover:shadow-glow focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-magenta disabled:opacity-50']) }}>
    {{ $slot }}
</button>
