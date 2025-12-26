<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeArticles extends Command
{
    protected $signature = 'articles:scrape';
    protected $description = 'Scrape the 5 oldest articles from BeyondChats blogs';

    public function handle()
    {
        $this->info('Starting to scrape articles from BeyondChats...');

        try {
            // First, we need to find the last page
            $baseUrl = 'https://beyondchats.com/blogs/';
            $lastPage = $this->findLastPage($baseUrl);
            
            if (!$lastPage) {
                $this->error('Could not find the last page. Trying to scrape from the main page...');
                $lastPage = $baseUrl;
            }

            $this->info("Found last page: {$lastPage}");
            
            // Scrape articles from the last page
            $articles = $this->scrapePage($lastPage);
            
            if (empty($articles)) {
                $this->error('No articles found on the page');
                return 1;
            }

            // Get the 5 oldest articles (first 5 from the page)
            $articlesToSave = array_slice($articles, 0, 5);
            
            $this->info('Found ' . count($articlesToSave) . ' articles to save');

            $saved = 0;
            foreach ($articlesToSave as $articleData) {
                // Check if article already exists
                $existing = Article::where('original_url', $articleData['url'])->first();
                
                if ($existing) {
                    $this->warn("Article '{$articleData['title']}' already exists, skipping...");
                    continue;
                }

                $article = Article::create([
                    'title' => $articleData['title'],
                    'content' => $articleData['content'],
                    'slug' => $this->generateUniqueSlug($articleData['title']),
                    'original_url' => $articleData['url'],
                    'excerpt' => $articleData['excerpt'] ?? substr(strip_tags($articleData['content']), 0, 200),
                    'author' => $articleData['author'] ?? null,
                    'published_at' => $articleData['published_at'] ?? now(),
                ]);

                $this->info("Saved: {$article->title}");
                $saved++;
            }

            $this->info("Successfully saved {$saved} articles!");
            return 0;
        } catch (\Exception $e) {
            $this->error('Error scraping articles: ' . $e->getMessage());
            return 1;
        }
    }

    private function findLastPage($baseUrl)
    {
        try {
            $response = Http::timeout(30)->get($baseUrl);
            
            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            
            // Look for pagination links
            // Common patterns: page/2, page/3, etc. or ?page=2, ?page=3
            preg_match_all('/href=["\']([^"\']*\/page\/\d+[^"\']*|.*[?&]page=\d+[^"\']*)["\']/i', $html, $matches);
            
            if (empty($matches[1])) {
                return $baseUrl; // No pagination found, return base URL
            }

            $pages = [];
            foreach ($matches[1] as $match) {
                if (preg_match('/(?:page\/|page=)(\d+)/i', $match, $pageMatch)) {
                    $pages[] = (int)$pageMatch[1];
                }
            }

            if (empty($pages)) {
                return $baseUrl;
            }

            $lastPageNum = max($pages);
            
            // Construct last page URL
            if (strpos($baseUrl, '?') !== false) {
                return preg_replace('/[?&]page=\d+/', '', $baseUrl) . '&page=' . $lastPageNum;
            } else {
                return rtrim($baseUrl, '/') . '/page/' . $lastPageNum;
            }
        } catch (\Exception $e) {
            $this->warn('Error finding last page: ' . $e->getMessage());
            return $baseUrl;
        }
    }

    private function scrapePage($url)
    {
        try {
            $this->info("Scraping: {$url}");
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                $this->error("Failed to fetch page: HTTP {$response->status()}");
                return [];
            }

            $html = $response->body();
            $articles = [];

            // Use DOMDocument to parse HTML
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);

            // Common blog article selectors - try multiple patterns
            $articleSelectors = [
                '//article',
                '//div[contains(@class, "post")]',
                '//div[contains(@class, "article")]',
                '//div[contains(@class, "blog-post")]',
                '//div[contains(@class, "entry")]',
            ];

            $articleNodes = null;
            foreach ($articleSelectors as $selector) {
                $articleNodes = $xpath->query($selector);
                if ($articleNodes && $articleNodes->length > 0) {
                    break;
                }
            }

            if (!$articleNodes || $articleNodes->length === 0) {
                // Fallback: try to find links that look like blog posts
                $linkNodes = $xpath->query('//a[contains(@href, "/blog/") or contains(@href, "/blogs/") or contains(@href, "/post/")]');
                
                if ($linkNodes && $linkNodes->length > 0) {
                    foreach ($linkNodes as $linkNode) {
                        $href = $linkNode->getAttribute('href');
                        if (empty($href) || strpos($href, '#') === 0) continue;
                        
                        // Make absolute URL
                        if (strpos($href, 'http') !== 0) {
                            $base = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
                            $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                        }

                        // Try to get title from link text or nearby elements
                        $title = trim($linkNode->textContent);
                        if (empty($title)) {
                            $titleNode = $xpath->query('.//h1 | .//h2 | .//h3', $linkNode->parentNode);
                            if ($titleNode && $titleNode->length > 0) {
                                $title = trim($titleNode->item(0)->textContent);
                            }
                        }

                        if (!empty($title) && !empty($href)) {
                            $articleContent = $this->scrapeArticleContent($href);
                            if ($articleContent) {
                                $articles[] = [
                                    'title' => $title,
                                    'content' => $articleContent,
                                    'url' => $href,
                                    'excerpt' => substr(strip_tags($articleContent), 0, 200),
                                ];
                            }
                        }
                    }
                }
            } else {
                // Process found article nodes
                foreach ($articleNodes as $articleNode) {
                    $articleData = $this->extractArticleData($articleNode, $xpath, $url);
                    if ($articleData) {
                        $articles[] = $articleData;
                    }
                }
            }

            return $articles;
        } catch (\Exception $e) {
            $this->error('Error scraping page: ' . $e->getMessage());
            return [];
        }
    }

    private function extractArticleData($articleNode, $xpath, $baseUrl)
    {
        try {
            // Try to find title
            $titleNodes = $xpath->query('.//h1 | .//h2 | .//h3 | .//a[contains(@class, "title")]', $articleNode);
            $title = '';
            if ($titleNodes && $titleNodes->length > 0) {
                $title = trim($titleNodes->item(0)->textContent);
            }

            // Try to find link
            $linkNodes = $xpath->query('.//a[@href]', $articleNode);
            $href = '';
            if ($linkNodes && $linkNodes->length > 0) {
                $href = $linkNodes->item(0)->getAttribute('href');
                if (strpos($href, 'http') !== 0) {
                    $base = parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST);
                    $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                }
            }

            if (empty($title) || empty($href)) {
                return null;
            }

            // Scrape full article content
            $content = $this->scrapeArticleContent($href);
            if (empty($content)) {
                // Fallback: try to get content from current node
                $contentNodes = $xpath->query('.//div[contains(@class, "content")] | .//div[contains(@class, "excerpt")] | .//p', $articleNode);
                $content = '';
                foreach ($contentNodes as $node) {
                    $content .= $node->textContent . "\n\n";
                }
            }

            return [
                'title' => $title,
                'content' => $content ?: 'Content not available',
                'url' => $href,
                'excerpt' => substr(strip_tags($content), 0, 200),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    private function scrapeArticleContent($url)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            $xpath = new \DOMXPath($dom);

            // Try multiple content selectors
            $contentSelectors = [
                '//article//div[contains(@class, "content")]',
                '//article//div[contains(@class, "entry-content")]',
                '//div[contains(@class, "post-content")]',
                '//div[contains(@class, "article-content")]',
                '//main//div[contains(@class, "content")]',
                '//article',
            ];

            $content = '';
            foreach ($contentSelectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    foreach ($nodes as $node) {
                        $content .= $dom->saveHTML($node);
                    }
                    if (!empty($content)) {
                        break;
                    }
                }
            }

            // Fallback: get all paragraphs
            if (empty($content)) {
                $paragraphs = $xpath->query('//main//p | //article//p');
                foreach ($paragraphs as $p) {
                    $content .= $dom->saveHTML($p);
                }
            }

            return $content ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateUniqueSlug($title)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (Article::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}


