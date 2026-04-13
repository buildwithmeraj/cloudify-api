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
    // user routes
    Route::get('user', function (Request $request) {
        return response()->json($request->user());
    })->name('user');
    Route::post('logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    })->name('logout');

    // cloudinary keys routes
    Route::get('/keys/cloudinary', [CloudinaryController::class, 'getKeys'])->name('cloudinary-keys.get');
    Route::post('/keys/cloudinary', [CloudinaryController::class, 'addKey'])->name('cloudinary-keys.add');
    Route::get('/keys/cloudinary/{id}', [CloudinaryController::class, 'getKey'])->name('cloudinary-keys.getKey');
    Route::put('/keys/cloudinary/{id}', [CloudinaryController::class, 'updateKey'])->name('cloudinary-keys.updateKey');
    Route::delete('/keys/cloudinary/{id}', [CloudinaryController::class, 'deleteKey'])->name('cloudinary-keys.deleteKey');

    // public keys routes
    Route::get('/keys/public', [PublicKeysController::class, 'getKeys'])->name('cloudinary-keys.get');
    Route::post('/keys/public', [PublicKeysController::class, 'addKey'])->name('cloudinary-keys.add');
    Route::get('/keys/public/{id}', [PublicKeysController::class, 'getKey'])->name('cloudinary-keys.getKey');
    Route::put('/keys/public/{id}', [PublicKeysController::class, 'updateKey'])->name('cloudinary-keys.updateKey');
    Route::delete('/keys/public/{id}', [PublicKeysController::class, 'deleteKey'])->name('cloudinary-keys.deleteKey');
});
