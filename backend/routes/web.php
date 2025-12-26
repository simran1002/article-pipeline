<?php

use Illuminate\Support\Facades\Route;

// Redirect root to API documentation or frontend
Route::get('/', function () {
    return response()->json([
        'message' => 'BeyondChats API',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /api/articles' => 'List all articles',
            'GET /api/articles/{id}' => 'Get single article',
            'GET /api/articles/latest' => 'Get latest article',
            'POST /api/articles' => 'Create article',
            'PUT /api/articles/{id}' => 'Update article',
            'DELETE /api/articles/{id}' => 'Delete article',
        ],
    ]);
});


