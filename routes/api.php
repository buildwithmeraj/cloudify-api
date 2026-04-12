<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use \App\Http\Controllers\Api\Keys\CloudinaryController;
use \App\Http\Controllers\Api\Keys\PublicKeysController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class)->name('register');
    Route::post('login', LoginController::class)->name('login');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', function (Request $request) {
        return response()->json($request->user());
    })->name('user');
    Route::post('logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    })->name('logout');
    Route::get('/keys/cloudinary', [CloudinaryController::class, 'getTokens'])->name('cloudinary-keys.get');
    Route::post('/keys/cloudinary', [CloudinaryController::class, 'addToken'])->name('cloudinary-keys.add');
    Route::get('/keys/public', [PublicKeysController::class, 'getTokens'])->name('cloudinary-keys.get');
    Route::post('/keys/public', [PublicKeysController::class, 'addToken'])->name('cloudinary-keys.add');
});
