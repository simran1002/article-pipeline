<?php

// Simple PHP development server script
// Run with: php -S localhost:8000 server.php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static files
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Route to API
if (strpos($uri, '/api/') === 0) {
    require_once __DIR__ . '/public/index.php';
    return;
}

// Default response
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
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

