<?php

namespace App\Http\Controllers;

use App\Actions\Reader\RecordReadingProgress;
use App\Enums\ReadingMode;
use App\Models\Chapter;
use App\Models\LibraryEntry;
use App\Models\Series;
use App\Models\Page;
use App\Services\Reader\ChapterAccess;
use App\Services\Reader\PageUrlFactory;
use App\Services\Security\PageWatermark;
use App\Services\Security\ReadRateGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function show(Series $series, string $number, ChapterAccess $access): View
    {
        abort_if($series->hidden, 404);

        $chapter = $this->chapter($series, $number);
        $user = auth()->user();
        $gate = $access->gate($chapter, $user);

        $progress = null;

        if ($user && ! $user->gizli_mod) {
            $progress = LibraryEntry::query()
                ->where('user_id', $user->id)
                ->where('series_id', $series->id)
                ->where('last_chapter_id', $chapter->id)
                ->first();
        }

        return view('reader.show', [
            'series' => $series,
            'chapter' => $chapter,
            'gate' => $gate,
            'mode' => $user?->okuma_modu ?: ReadingMode::Webtoon->value,
            'stealth' => (bool) $user?->gizli_mod,
            'progress' => $progress,
            'neighbors' => $this->neighbors($chapter),
        ]);
    }

    public function pages(Series $series, string $number, ChapterAccess $access, PageUrlFactory $urls, PageWatermark $watermark, ReadRateGuard $rates): JsonResponse
    {
        abort_if($series->hidden, 404);

        $user = auth()->user();

        if ($user && $user->read_blocked_until?->isFuture()) {
            $rates->block(request(), 'hesap');

            return response()->json([
                'message' => 'Okuma geçici olarak durduruldu.',
            ], 429);
        }

        $chapter = $this->chapter($series, $number);
        $gate = $access->gate($chapter, $user);

        if ($gate['locked']) {
            return response()->json([
                'locked' => true,
                'reason' => $gate['reason'],
                'price' => $gate['price'],
                'vip_free' => $gate['vip_free'],
            ]);
        }

        $pages = $chapter->pages()->orderBy('order')->get();

        return response()->json([
            'locked' => false,
            'watermark' => $watermark->text($user),
            'pages' => $pages->map(fn ($page) => [
                'order' => $page->order,
                'url' => $urls->temporary($page),
                'width' => $page->width,
                'height' => $page->height,
            ])->values(),
        ]);
    }

    public function media(Request $request, Page $page, ChapterAccess $access, PageUrlFactory $urls)
    {
        abort_unless($request->hasValidSignature(), 403);

        $page->loadMissing('chapter.series');
        $chapter = $page->chapter;
        abort_if($chapter === null || $chapter->series === null || $chapter->series->hidden, 404);
        abort_if($access->isLocked($chapter, $request->user()), 403);

        return $urls->stream($page);
    }

    public function progress(Request $request, Series $series, string $number, ChapterAccess $access, RecordReadingProgress $record): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->gizli_mod) {
            return response()->json(['saved' => false, 'stealth' => true]);
        }

        $chapter = $this->chapter($series, $number);
        abort_if($access->isLocked($chapter, $user), 403);

        $validated = $request->validate([
            'page' => ['required', 'integer', 'min:1'],
        ]);

        $count = $chapter->pages()->count();
        $record->handle($user, $chapter, (int) $validated['page'], $count);

        return response()->json(['saved' => true]);
    }

    public function mode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::enum(ReadingMode::class)],
        ]);

        $request->user()->forceFill(['okuma_modu' => $validated['mode']])->save();

        return response()->json(['mode' => $validated['mode']]);
    }

    public function history(): View
    {
        $entries = LibraryEntry::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('last_chapter_id')
            ->whereNotNull('last_read_at')
            ->with(['series', 'lastChapter'])
            ->latest('last_read_at')
            ->paginate(20);

        return view('reader.history', [
            'entries' => $entries,
        ]);
    }

    private function chapter(Series $series, string $number): Chapter
    {
        return Chapter::query()
            ->where('series_id', $series->id)
            ->where('number', $number)
            ->firstOrFail();
    }

    /**
     * @return array{previous: ?Chapter, next: ?Chapter}
     */
    private function neighbors(Chapter $chapter): array
    {
        $base = Chapter::query()
            ->where('series_id', $chapter->series_id)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());

        return [
            'previous' => (clone $base)->where('number', '<', $chapter->number)->orderByDesc('number')->first(),
            'next' => (clone $base)->where('number', '>', $chapter->number)->orderBy('number')->first(),
        ];
    }
}
