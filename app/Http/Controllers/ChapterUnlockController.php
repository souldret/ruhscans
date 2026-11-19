<?php

namespace App\Http\Controllers;

use App\Exceptions\Economy\InsufficientBalanceException;
use App\Models\Chapter;
use App\Models\Series;
use App\Services\Economy\ChapterUnlocker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChapterUnlockController extends Controller
{
    public function store(Request $request, Series $series, string $number, ChapterUnlocker $unlocker): RedirectResponse
    {
        abort_if($series->hidden, 404);

        $chapter = Chapter::query()
            ->where('series_id', $series->id)
            ->where('number', $number)
            ->firstOrFail();

        try {
            $unlocker->unlock($request->user(), $chapter);
        } catch (InsufficientBalanceException) {
            return back()->withErrors(['unlock' => 'Ruh taşı bakiyen yetmiyor.']);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['unlock' => $exception->getMessage()]);
        }

        return redirect()->route('reader.show', [$series, $chapter->number]);
    }
}
