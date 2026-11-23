@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-14 w-full rounded-2xl border border-white/10 bg-ink px-4 text-base text-mist placeholder:text-mist/40 focus:border-magenta focus:ring-magenta']) }}>
