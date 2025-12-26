<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Support\Facades\Route;

// API Routes
Route::prefix('api/articles')->group(function () {
    Route::get('/', [ArticleController::class, 'index']);
    Route::get('/latest', [ArticleController::class, 'latest']);
    Route::get('/{id}', [ArticleController::class, 'show']);
    Route::get('/slug/{slug}', [ArticleController::class, 'showBySlug']);
    Route::post('/', [ArticleController::class, 'store']);
    Route::put('/{id}', [ArticleController::class, 'update']);
    Route::delete('/{id}', [ArticleController::class, 'destroy']);
});
