<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdultGateController;
use App\Http\Controllers\CoinCodeBatchExportController;
use App\Http\Controllers\CoinPackageController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameReviewController;
use App\Http\Controllers\GameUnlockController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SignedDownloadController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SupportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/health', HealthController::class)->name('health');
Route::post('/yas-onayi', [AdultGateController::class, 'store'])->name('adult.confirm');
Route::post('/cerez-bildirimi', [AdultGateController::class, 'notice'])->name('cookies.notice');
Route::get('/oyunlar', [GameController::class, 'index'])
    ->middleware('throttle:60,1')
    ->name('games.index');
Route::get('/oyun/{game:slug}', [GameController::class, 'show'])->name('games.show');
Route::permanentRedirect('/vip', '/coin-paketleri')->name('vip');
Route::get('/coin-paketleri', [CoinPackageController::class, 'index'])->name('coins');
Route::get('/git/{package}', [CoinPackageController::class, 'go'])->middleware('throttle:30,1')->name('coins.go');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::view('/kullanim-sartlari', 'legal.sartlar')->name('legal.terms');
Route::view('/gizlilik', 'legal.gizlilik')->name('legal.privacy');
Route::view('/cerez-politikasi', 'legal.cerez')->name('legal.cookies');
Route::view('/hakkimizda', 'legal.hakkimizda')->name('legal.about');
Route::view('/icerik-kaldirma', 'legal.kaldirma')->name('legal.takedown');
Route::post('/icerik-kaldirma', [LegalController::class, 'takedown'])
    ->middleware('throttle:5,1')
    ->name('legal.takedown.store');
Route::get('/indir/{file}', DownloadController::class)
    ->middleware('throttle:30,1')
    ->name('downloads.show');
Route::get('/indir/{file}/imzali', SignedDownloadController::class)
    ->middleware('signed')
    ->name('downloads.signed');

Route::get('/dashboard', function () {
    return redirect()->route('account.show');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/destek', [SupportController::class, 'index'])->name('support.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/destek', [SupportController::class, 'store'])->middleware('throttle:10,1')->name('support.store');
    Route::get('/destek/{thread}', [SupportController::class, 'show'])->name('support.show');
    Route::post('/destek/{thread}', [SupportController::class, 'reply'])->middleware('throttle:20,1')->name('support.reply');
    Route::post('/destek/{thread}/kapat', [SupportController::class, 'close'])->name('support.close');
    Route::get('/hesap', [AccountController::class, 'show'])->name('account.show');
    Route::post('/hesap/kod-kullan', [AccountController::class, 'redeem'])->middleware('throttle:5,1')->name('account.redeem');
    Route::post('/oyun/{game:slug}/ac', [GameUnlockController::class, 'store'])->middleware('throttle:10,1')->name('games.unlock');
    Route::post('/oyun/{game:slug}/yorum', [GameReviewController::class, 'store'])->name('games.reviews.store');
    Route::delete('/yorum/{review}', [GameReviewController::class, 'destroy'])->name('games.reviews.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/yonetim/coin-partiler/{batch}/indir/{format}', CoinCodeBatchExportController::class)
        ->name('admin.coin-code-batches.export');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
