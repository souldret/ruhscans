<?php

use App\Http\Controllers\Admin\TwoFactorChallengeController;
use App\Http\Controllers\AgeGateController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\TagController;
use App\Livewire\DiscoverCatalog;
use App\Livewire\ReadingPreferences;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/kesfet', DiscoverCatalog::class)->name('discover');
Route::get('/etiket/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
Route::get('/seri/{series:slug}', [SeriesController::class, 'show'])->name('series.show');
Route::get('/seri/{series:slug}/bolum/{number}', [ReaderController::class, 'show'])->name('reader.show');
Route::get('/seri/{series:slug}/bolum/{number}/sayfalar', [ReaderController::class, 'pages'])->name('reader.pages');

Route::middleware('auth')->group(function () {
    Route::post('/seri/{series:slug}/bolum/{number}/ilerleme', [ReaderController::class, 'progress'])->name('reader.progress');
    Route::post('/okuma-modu', [ReaderController::class, 'mode'])->name('reader.mode');
    Route::get('/okuma-gecmisi', [ReaderController::class, 'history'])->name('reader.history');
});

Route::get('/kutuphane', LibraryController::class)->middleware('auth')->name('library');
Route::get('/okuma-tercihleri', ReadingPreferences::class)->middleware('auth')->name('preferences');

Route::post('/18-onay', [AgeGateController::class, 'confirm'])->name('age-gate.confirm');

Route::get('/kullanim-sartlari', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/gizlilik', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/dmca', [LegalPageController::class, 'dmca'])->name('legal.dmca');
Route::get('/18-uyari', [LegalPageController::class, 'age'])->name('legal.age');
Route::get('/sikayet', [LegalPageController::class, 'report'])->name('legal.report');

Route::view('/robots.txt', 'seo.robots')->name('robots');
Route::view('/sitemap.xml', 'seo.sitemap')->name('sitemap');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware('auth')->prefix('admin/iki-adim')->group(function () {
    Route::get('/', [TwoFactorChallengeController::class, 'create'])->name('admin.two-factor');
    Route::post('/', [TwoFactorChallengeController::class, 'store'])->name('admin.two-factor.store');
});

require __DIR__.'/auth.php';
