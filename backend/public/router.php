<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle API routes first
if (strpos($uri, '/api/') === 0) {
    $_SERVER['REQUEST_URI'] = $uri; // Preserve original URI
    require __DIR__ . '/api.php';
    return true;
}

// Serve static files
if ($uri !== '/' && $uri !== '' && file_exists(__DIR__ . $uri)) {
    return false;
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
    ],
]);
return true;

