<?php

use App\Http\Controllers\Admin\DirectZipUploadController;
use App\Http\Controllers\AgeGateController;
use App\Http\Controllers\ChapterUnlockController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalNoticeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\RedeemCodeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\ThrottlePageReads;
use App\Livewire\DiscoverCatalog;
use App\Livewire\ReadingPreferences;
use App\Models\User;
use App\Services\Economy\CodeExportStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/kesfet', DiscoverCatalog::class)->name('discover');
Route::get('/etiket/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/seri/{series:slug}', [SeriesController::class, 'show'])->name('series.show');
Route::get('/seri/{series:slug}/bolum/{number}', [ReaderController::class, 'show'])->name('reader.show');
Route::get('/seri/{series:slug}/bolum/{number}/sayfalar', [ReaderController::class, 'pages'])
    ->middleware(ThrottlePageReads::class)
    ->name('reader.pages');
Route::get('/sayfa/{page}', [ReaderController::class, 'media'])
    ->name('reader.media');

Route::middleware('auth')->group(function () {
    Route::get('/cuzdan', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('/cuzdan/kod', [RedeemCodeController::class, 'store'])->middleware('throttle:30,1')->name('wallet.redeem');
    Route::post('/seri/{series:slug}/bolum/{number}/ac', [ChapterUnlockController::class, 'store'])->middleware('throttle:20,1')->name('reader.unlock');
    Route::post('/seri/{series:slug}/bolum/{number}/ilerleme', [ReaderController::class, 'progress'])->middleware('throttle:120,1')->name('reader.progress');
    Route::post('/okuma-modu', [ReaderController::class, 'mode'])->middleware('throttle:30,1')->name('reader.mode');
    Route::get('/okuma-gecmisi', [ReaderController::class, 'history'])->name('reader.history');
});

Route::get('/kutuphane', LibraryController::class)->middleware('auth')->name('library');
Route::get('/okuma-tercihleri', ReadingPreferences::class)->middleware('auth')->name('preferences');

Route::post('/18-onay', [AgeGateController::class, 'confirm'])->middleware('throttle:30,1')->name('age-gate.confirm');

Route::get('/kullanim-sartlari', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/gizlilik', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/dmca', [LegalNoticeController::class, 'dmcaForm'])->name('legal.dmca');
Route::get('/18-uyari', [LegalPageController::class, 'age'])->name('legal.age');
Route::get('/sikayet', [LegalNoticeController::class, 'reportForm'])->name('legal.report');
Route::post('/bildirim', [LegalNoticeController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('legal.notice.store');

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'index'])->name('sitemap');
Route::get('/sitemap-{page}.xml', [SeoController::class, 'page'])->whereNumber('page')->name('sitemap.page');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/profile/veri-indir', [ProfileController::class, 'export'])
        ->middleware('throttle:5,10')
        ->name('profile.export');
    Route::get('/profile/avatar/{user}', [ProfileController::class, 'avatar'])->name('profile.avatar');
});

Route::middleware(['auth', 'throttle:30,1'])->prefix('admin/zip')->name('admin.zip.direct.')->group(function () {
    Route::post('/presign', [DirectZipUploadController::class, 'presign'])->name('presign');
    Route::post('/multipart', [DirectZipUploadController::class, 'startMultipart'])->name('multipart.start');
    Route::post('/multipart/tamamla', [DirectZipUploadController::class, 'completeMultipart'])->name('multipart.complete');
    Route::post('/tamamla', [DirectZipUploadController::class, 'complete'])->name('complete');
});

Route::middleware(['auth', 'throttle:10,1'])->get('/admin/kodlar/indir', function (Request $request, CodeExportStore $store) {
    $user = $request->user();
    abort_unless($user instanceof User && $user->hasRole(User::ROLE_ADMIN) && ! $user->isBlocked(), 403);

    $consumed = $store->pullAndConsume((string) $request->query('token'));
    abort_unless($consumed !== null, 404);

    activity('economy')
        ->causedBy($user)
        ->withProperties(['batch_id' => $consumed['batch_id']])
        ->log('Tek seferlik kod CSV dosyası indirildi.');

    return response($consumed['csv'], 200, [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="ruhscans-kod-'.$consumed['batch_id'].'.csv"',
        'Cache-Control' => 'no-store, private',
    ]);
})->name('admin.codes.csv');

require __DIR__.'/auth.php';
