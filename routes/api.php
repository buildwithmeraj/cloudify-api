<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use \App\Http\Controllers\Api\Keys\CloudinaryController;
use \App\Http\Controllers\Api\Keys\PublicKeysController;
use \App\Http\Controllers\Api\Cloudinary\CloudinaryFilesController;
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
    Route::get('/keys/cloudinary', [CloudinaryController::class, 'getCloudinaryKeys'])->name('cloudinary-keys.getCloudinaryKeys');
    Route::post('/keys/cloudinary', [CloudinaryController::class, 'addCloudinaryKey'])->name('cloudinary-keys.addCloudinaryKey');
    Route::get('/keys/cloudinary/{id}', [CloudinaryController::class, 'getCloudinaryKey'])->name('cloudinary-keys.getCloudinaryKey');
    Route::put('/keys/cloudinary/{id}', [CloudinaryController::class, 'updateCloudinaryKey'])->name('cloudinary-keys.updateCloudinaryKey');
    Route::delete('/keys/cloudinary/{id}', [CloudinaryController::class, 'deleteCloudinaryKey'])->name('cloudinary-keys.deleteCloudinaryKey');

    // public keys routes
    Route::get('/keys/public', [PublicKeysController::class, 'getPublicKeys'])->name('cloudinary-keys.getPublicKeys');
    Route::post('/keys/public', [PublicKeysController::class, 'addPublicKey'])->name('cloudinary-keys.addPublicKey');
    Route::get('/keys/public/{id}', [PublicKeysController::class, 'getPublicKey'])->name('cloudinary-keys.getPublicKey');
    Route::put('/keys/public/{id}', [PublicKeysController::class, 'updatePublicKey'])->name('cloudinary-keys.updatePublicKey');
    Route::delete('/keys/public/{id}', [PublicKeysController::class, 'deletePublicKey'])->name('cloudinary-keys.deletePublicKey');


});
// cloudinary routes
Route::post('/cloudinary/upload', [CloudinaryFilesController::class, 'uploadFile'])->name('cloudinary.upload');
Route::get('/cloudinary/files', [CloudinaryFilesController::class, 'listFiles'])->name('cloudinary.files');
Route::delete('/cloudinary/files', [CloudinaryFilesController::class, 'deleteFiles'])->name('cloudinary.files.delete');


// fallback route for undefined API endpoints
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint not found.'], 404);
});
