<div class="space-y-2 text-sm">
    @forelse ($failures as $failure)
        <p><strong>{{ $failure['file'] }}</strong> — {{ $failure['message'] }}</p>
    @empty
        <p>Hata yok.</p>
    @endforelse
</div>
