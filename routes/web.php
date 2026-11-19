<?php

use App\Http\Controllers\Admin\TwoFactorChallengeController;
use App\Http\Controllers\AgeGateController;
use App\Http\Controllers\LegalPageController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

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
