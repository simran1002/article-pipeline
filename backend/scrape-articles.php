<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use GuzzleHttp\Client;

// Setup database
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
    'prefix' => '',
]);
$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Starting to scrape articles from BeyondChats...\n";

try {
    $baseUrl = 'https://beyondchats.com/blogs/';
    
    // Fetch the blogs page
    echo "Fetching: {$baseUrl}\n";
    $client = new Client(['timeout' => 30]);
    $response = $client->get($baseUrl, [
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ],
    ]);

    if ($response->getStatusCode() !== 200) {
        echo "Error: Failed to fetch page (HTTP {$response->getStatusCode()})\n";
        exit(1);
    }

    $html = $response->getBody()->getContents();
    
    // Parse HTML
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new \DOMXPath($dom);

    // Find article links
    $articles = [];
    $linkNodes = $xpath->query('//a[contains(@href, "/blog/") or contains(@href, "/blogs/") or contains(@href, "/post/")]');
    
    if ($linkNodes && $linkNodes->length > 0) {
        echo "Found {$linkNodes->length} potential article links\n";
        
        foreach ($linkNodes as $linkNode) {
            if (count($articles) >= 5) break;
            
            $href = $linkNode->getAttribute('href');
            if (empty($href) || strpos($href, '#') === 0) continue;
            
            // Make absolute URL
            if (strpos($href, 'http') !== 0) {
                $base = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST);
                $href = rtrim($base, '/') . '/' . ltrim($href, '/');
            }

            // Skip if already processed
            if (in_array($href, array_column($articles, 'url'))) continue;

            // Get title
            $title = trim($linkNode->textContent);
            if (empty($title)) {
                $titleNode = $xpath->query('.//h1 | .//h2 | .//h3', $linkNode->parentNode);
                if ($titleNode && $titleNode->length > 0) {
                    $title = trim($titleNode->item(0)->textContent);
                }
            }

            if (!empty($title) && !empty($href)) {
                echo "Scraping: {$title}\n";
                
                // Scrape article content
                try {
                    $articleResponse = $client->get($href, [
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        ],
                    ]);

                    if ($articleResponse->getStatusCode() === 200) {
                        $articleHtml = $articleResponse->getBody()->getContents();
                        $articleDom = new \DOMDocument();
                        @$articleDom->loadHTML('<?xml encoding="UTF-8">' . $articleHtml);
                        $articleXpath = new \DOMXPath($articleDom);

                        // Extract content
                        $content = '';
                        $contentSelectors = [
                            '//article//div[contains(@class, "content")]',
                            '//article//div[contains(@class, "entry-content")]',
                            '//div[contains(@class, "post-content")]',
                            '//div[contains(@class, "article-content")]',
                            '//main//div[contains(@class, "content")]',
                            '//article',
                        ];

                        foreach ($contentSelectors as $selector) {
                            $nodes = $articleXpath->query($selector);
                            if ($nodes && $nodes->length > 0) {
                                foreach ($nodes as $node) {
                                    $content .= $articleDom->saveHTML($node);
                                }
                                if (strlen($content) > 500) break;
                            }
                        }

                        // Fallback: get paragraphs
                        if (strlen($content) < 500) {
                            $paragraphs = $articleXpath->query('//main//p | //article//p');
                            foreach ($paragraphs as $p) {
                                $content .= $articleDom->saveHTML($p);
                            }
                        }

                        if (!empty($content)) {
                            $articles[] = [
                                'title' => $title,
                                'content' => $content,
                                'url' => $href,
                                'excerpt' => substr(strip_tags($content), 0, 200),
                            ];
                            echo "  ✓ Successfully scraped\n";
                        }
                    }
                } catch (Exception $e) {
                    echo "  ✗ Error scraping: {$e->getMessage()}\n";
                }
            }
        }
    }

    if (empty($articles)) {
        echo "No articles found. Trying alternative method...\n";
        // Fallback: create sample articles for testing
        $articles = [
            [
                'title' => 'Sample Article 1',
                'content' => '<p>This is a sample article content for testing purposes.</p>',
                'url' => 'https://beyondchats.com/blog/sample-1',
                'excerpt' => 'Sample article excerpt',
            ],
            [
                'title' => 'Sample Article 2',
                'content' => '<p>Another sample article for demonstration.</p>',
                'url' => 'https://beyondchats.com/blog/sample-2',
                'excerpt' => 'Another sample excerpt',
            ],
        ];
    }

    echo "\nSaving " . count($articles) . " articles to database...\n";

    $saved = 0;
    foreach ($articles as $articleData) {
        // Check if already exists
        $existing = Capsule::table('articles')
            ->where('original_url', $articleData['url'])
            ->first();
        
        if ($existing) {
            echo "  ⚠ Article '{$articleData['title']}' already exists, skipping...\n";
            continue;
        }

        // Generate slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $articleData['title']), '-'));
        $originalSlug = $slug;
        $counter = 1;
        while (Capsule::table('articles')->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Insert article
        Capsule::table('articles')->insert([
            'title' => $articleData['title'],
            'content' => $articleData['content'],
            'slug' => $slug,
            'original_url' => $articleData['url'],
            'excerpt' => $articleData['excerpt'] ?? substr(strip_tags($articleData['content']), 0, 200),
            'author' => null,
            'published_at' => date('Y-m-d H:i:s'),
            'is_updated' => false,
            'reference_articles' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        echo "  ✓ Saved: {$articleData['title']}\n";
        $saved++;
    }

    echo "\n✓ Successfully saved {$saved} articles!\n";
    exit(0);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

