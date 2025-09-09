<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\ThumbnailController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('movies', MovieController::class);
Route::get('videos/{filename}', [VideoController::class, 'stream']);
Route::get('movies/{id}/thumbnail', [ThumbnailController::class, 'generate']);
Route::get('thumbnails/{filename}', [ThumbnailController::class, 'serve']);