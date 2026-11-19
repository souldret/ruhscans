<?php

namespace App\Http\Controllers;

use App\Actions\Legal\SubmitReport;
use App\Models\Chapter;
use App\Models\Series;
use App\Services\Seo\MetaBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalNoticeController extends Controller
{
    public function reportForm(Request $request, MetaBuilder $meta): View
    {
        return view('legal.report', $this->prefill($request) + [
            'kind' => 'sikayet',
            'seo' => $meta->legal('Şikayet'),
        ]);
    }

    public function dmcaForm(Request $request, MetaBuilder $meta): View
    {
        return view('legal.report', $this->prefill($request) + [
            'kind' => 'dmca',
            'seo' => $meta->legal('Telif bildirimi'),
        ]);
    }

    public function store(Request $request, SubmitReport $submit): RedirectResponse
    {
        if (filled($request->input('website'))) {
            return back()->with('notice', 'Bildirim alındı.');
        }

        $validated = $request->validate([
            'kind' => ['required', 'in:sikayet,dmca'],
            'email' => ['required', 'email', 'max:255'],
            'url' => ['nullable', 'url', 'max:500'],
            'series_slug' => ['nullable', 'string', 'max:255'],
            'chapter_number' => ['nullable', 'numeric'],
            'reason' => ['required', 'string', 'min:20', 'max:5000'],
        ]);

        $submit->handle($validated, $request->user()?->id);

        return back()->with('notice', 'Bildirim alındı. İnceleme kuyruğuna eklendi.');
    }

    /**
     * @return array{series: ?Series, chapter: ?Chapter, url: string}
     */
    private function prefill(Request $request): array
    {
        $series = null;
        $chapter = null;

        if ($request->filled('seri')) {
            $series = Series::query()->where('slug', $request->string('seri'))->first();
        }

        if ($series && $request->filled('bolum')) {
            $chapter = Chapter::query()
                ->where('series_id', $series->id)
                ->where('number', $request->string('bolum'))
                ->first();
        }

        return [
            'series' => $series,
            'chapter' => $chapter,
            'url' => $request->string('adres')->toString(),
        ];
    }
}
