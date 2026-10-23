<a href="{{ route('games.show', $game) }}" class="block rounded-lg border border-zinc-800 bg-zinc-900 p-4 hover:border-rose-700">
    <h2 class="text-lg font-medium">{{ $game->title }}</h2>
    <p class="mt-2 line-clamp-2 text-sm text-zinc-400">{{ $game->description }}</p>
    <p class="mt-3 text-xs text-zinc-500">{{ $game->downloads_count }} indirme</p>
</a>
