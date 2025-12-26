<?php

// Simple API handler for development
header('Access-Control-Allow-Origin: http://localhost:3001');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Try to use database if available
$useDatabase = false;
$dbFile = __DIR__ . '/../database/database.sqlite';

if (extension_loaded('pdo_sqlite') && file_exists($dbFile) && filesize($dbFile) > 0) {
    try {
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => $dbFile,
            'prefix' => '',
        ]);
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        $useDatabase = true;
    } catch (Exception $e) {
        // Fall back to empty array
        $useDatabase = false;
    }
}

// Route: GET /api/articles
if ($method === 'GET' && ($uri === '/api/articles' || $uri === '/api/articles/')) {
    if ($useDatabase) {
        try {
            $articles = Capsule::table('articles')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'content' => $article->content,
                        'slug' => $article->slug,
                        'original_url' => $article->original_url,
                        'excerpt' => $article->excerpt,
                        'author' => $article->author,
                        'published_at' => $article->published_at,
                        'is_updated' => (bool)$article->is_updated,
                        'reference_articles' => $article->reference_articles ? json_decode($article->reference_articles, true) : [],
                        'created_at' => $article->created_at,
                        'updated_at' => $article->updated_at,
                    ];
                });
            
            http_response_code(200);
            echo json_encode($articles->toArray());
            exit;
        } catch (Exception $e) {
            // Fall through to empty array
        }
    }
    
    // Return empty array (no database or no articles)
    http_response_code(200);
    echo json_encode([]);
    exit;
}

// Route: GET /api/articles/latest
if ($method === 'GET' && $uri === '/api/articles/latest') {
    if ($useDatabase) {
        try {
            $article = Capsule::table('articles')
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($article) {
                http_response_code(200);
                echo json_encode([
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'slug' => $article->slug,
                    'original_url' => $article->original_url,
                    'excerpt' => $article->excerpt,
                    'author' => $article->author,
                    'published_at' => $article->published_at,
                    'is_updated' => (bool)$article->is_updated,
                    'reference_articles' => $article->reference_articles ? json_decode($article->reference_articles, true) : [],
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ]);
                exit;
            }
        } catch (Exception $e) {
            // Fall through
        }
    }
    
    http_response_code(404);
    echo json_encode(['message' => 'No articles found']);
    exit;
}

// Route: GET /api/articles/{id}
if ($method === 'GET' && preg_match('#^/api/articles/(\d+)$#', $uri, $matches)) {
    if ($useDatabase) {
        try {
            $article = Capsule::table('articles')->where('id', $matches[1])->first();
            
            if ($article) {
                http_response_code(200);
                echo json_encode([
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'slug' => $article->slug,
                    'original_url' => $article->original_url,
                    'excerpt' => $article->excerpt,
                    'author' => $article->author,
                    'published_at' => $article->published_at,
                    'is_updated' => (bool)$article->is_updated,
                    'reference_articles' => $article->reference_articles ? json_decode($article->reference_articles, true) : [],
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ]);
                exit;
            }
        } catch (Exception $e) {
            // Fall through
        }
    }
    
    http_response_code(404);
    echo json_encode(['message' => 'Article not found']);
    exit;
}

// Route: POST /api/articles
if ($method === 'POST' && $uri === '/api/articles') {
    if ($useDatabase) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $id = Capsule::table('articles')->insertGetId([
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? '',
                'slug' => $data['slug'] ?? strtolower(str_replace(' ', '-', $data['title'] ?? '')),
                'original_url' => $data['original_url'] ?? null,
                'excerpt' => $data['excerpt'] ?? null,
                'author' => $data['author'] ?? null,
                'published_at' => $data['published_at'] ?? date('Y-m-d H:i:s'),
                'is_updated' => $data['is_updated'] ?? false,
                'reference_articles' => isset($data['reference_articles']) ? json_encode($data['reference_articles']) : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            
            $article = Capsule::table('articles')->where('id', $id)->first();
            
            http_response_code(201);
            echo json_encode([
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'slug' => $article->slug,
                'original_url' => $article->original_url,
                'excerpt' => $article->excerpt,
                'author' => $article->author,
                'published_at' => $article->published_at,
                'is_updated' => (bool)$article->is_updated,
                'reference_articles' => $article->reference_articles ? json_decode($article->reference_articles, true) : [],
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    http_response_code(503);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

// Route: PUT /api/articles/{id}
if ($method === 'PUT' && preg_match('#^/api/articles/(\d+)$#', $uri, $matches)) {
    if ($useDatabase) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $updateData = [];
            if (isset($data['title'])) $updateData['title'] = $data['title'];
            if (isset($data['content'])) $updateData['content'] = $data['content'];
            if (isset($data['slug'])) $updateData['slug'] = $data['slug'];
            if (isset($data['original_url'])) $updateData['original_url'] = $data['original_url'];
            if (isset($data['excerpt'])) $updateData['excerpt'] = $data['excerpt'];
            if (isset($data['author'])) $updateData['author'] = $data['author'];
            if (isset($data['published_at'])) $updateData['published_at'] = $data['published_at'];
            if (isset($data['is_updated'])) $updateData['is_updated'] = $data['is_updated'];
            if (isset($data['reference_articles'])) $updateData['reference_articles'] = json_encode($data['reference_articles']);
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            
            Capsule::table('articles')->where('id', $matches[1])->update($updateData);
            
            $article = Capsule::table('articles')->where('id', $matches[1])->first();
            
            if ($article) {
                http_response_code(200);
                echo json_encode([
                    'id' => $article->id,
                    'title' => $article->title,
                    'content' => $article->content,
                    'slug' => $article->slug,
                    'original_url' => $article->original_url,
                    'excerpt' => $article->excerpt,
                    'author' => $article->author,
                    'published_at' => $article->published_at,
                    'is_updated' => (bool)$article->is_updated,
                    'reference_articles' => $article->reference_articles ? json_decode($article->reference_articles, true) : [],
                    'created_at' => $article->created_at,
                    'updated_at' => $article->updated_at,
                ]);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    http_response_code(503);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

// Route: DELETE /api/articles/{id}
if ($method === 'DELETE' && preg_match('#^/api/articles/(\d+)$#', $uri, $matches)) {
    if ($useDatabase) {
        try {
            $deleted = Capsule::table('articles')->where('id', $matches[1])->delete();
            
            if ($deleted) {
                http_response_code(200);
                echo json_encode(['message' => 'Article deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['message' => 'Article not found']);
            }
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    http_response_code(503);
    echo json_encode(['error' => 'Database not available']);
    exit;
}

// Default 404
http_response_code(404);
echo json_encode(['error' => 'Not Found']);
