<?php

use App\Http\Controllers\FreeScoutWebhookController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\GranolaOAuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// ── Granola OAuth ───────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/granola/redirect', [GranolaOAuthController::class, 'redirect'])->name('granola.redirect');
    Route::get('/granola/callback', [GranolaOAuthController::class, 'callback'])->name('granola.callback');

    Route::get('/google/redirect', [GoogleOAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('/google/callback', [GoogleOAuthController::class, 'callback'])->name('google.callback');
});

// ── FreeScout Webhook ──────────────────────────────────────────────
Route::post('/webhooks/freescout', [FreeScoutWebhookController::class, 'handle'])
    ->name('freescout.webhook');
