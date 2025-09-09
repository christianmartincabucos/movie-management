<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\ThumbnailController;
use App\Http\Controllers\VideoController;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{id}', [MovieController::class, 'show']);
Route::post('/movies', [MovieController::class, 'store']);
Route::put('/movies/{id}', [MovieController::class, 'update']);
Route::delete('/movies/{id}', [MovieController::class, 'destroy']);

Route::get('storage/{path}', function ($path) {
    $file = Storage::disk('public')->get($path);
    $type = Storage::disk('public')->mimeType($path);
    
    $response = Response::make($file, 200);
    $response->header('Content-Type', $type);
    
    // Add CORS headers
    $response->header('Access-Control-Allow-Origin', '*');
    $response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
    $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept');
    
    return $response;
})->where('path', '.*');