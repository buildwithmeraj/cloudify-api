<?php

use App\Http\Controllers\Api\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/google/redirect', [SocialiteController::class, 'redirect']);
Route::get('/auth/google/callback', [SocialiteController::class, 'callback']);
Route::fallback(function () {
    return redirect(config('app.frontend_url') . '/404');
});
