require('dotenv').config();
const axios = require('axios');
const cheerio = require('cheerio');
const { OpenAI } = require('openai');

class ArticleEnhancementService {
    constructor() {
        this.laravelApiUrl = process.env.LARAVEL_API_URL || 'http://localhost:8000';
        this.googleApiKey = process.env.GOOGLE_API_KEY;
        this.googleCseId = process.env.GOOGLE_CSE_ID;
        
        if (process.env.OPENAI_API_KEY) {
            this.openai = new OpenAI({
                apiKey: process.env.OPENAI_API_KEY,
            });
        } else {
            console.warn('Warning: OPENAI_API_KEY not set. LLM functionality will be limited.');
        }
    }

    async enhanceLatestArticle() {
        try {
            console.log('Starting article enhancement process...');
            
            // Step 1: Fetch latest article from Laravel API
            console.log('Step 1: Fetching latest article...');
            const article = await this.fetchLatestArticle();
            
            if (!article) {
                console.error('No articles found in the database');
                return;
            }

            console.log(`Found article: "${article.title}"`);

            // Step 2: Search article title on Google
            console.log('Step 2: Searching Google for similar articles...');
            const searchResults = await this.searchGoogle(article.title);
            
            if (!searchResults || searchResults.length < 2) {
                console.error('Could not find enough search results (need at least 2)');
                return;
            }

            console.log(`Found ${searchResults.length} search results`);

            // Step 3: Scrape content from top 2 articles
            console.log('Step 3: Scraping content from top 2 articles...');
            const referenceArticles = [];
            
            for (let i = 0; i < Math.min(2, searchResults.length); i++) {
                const result = searchResults[i];
                console.log(`Scraping: ${result.title} (${result.link})`);
                
                const content = await this.scrapeArticleContent(result.link);
                if (content) {
                    referenceArticles.push({
                        url: result.link,
                        title: result.title,
                        content: content,
                    });
                    console.log(`Successfully scraped article ${i + 1}`);
                } else {
                    console.warn(`Failed to scrape article ${i + 1}`);
                }
            }

            if (referenceArticles.length < 2) {
                console.error('Could not scrape enough articles (need at least 2)');
                return;
            }

            // Step 4: Call LLM API to update the article
            console.log('Step 4: Enhancing article with LLM...');
            const enhancedContent = await this.enhanceWithLLM(
                article,
                referenceArticles
            );

            if (!enhancedContent) {
                console.error('Failed to enhance article with LLM');
                return;
            }

            // Step 5: Publish updated article via Laravel API
            console.log('Step 5: Publishing enhanced article...');
            const referenceUrls = referenceArticles.map(ref => ref.url);
            
            const updatedArticle = await this.publishEnhancedArticle(
                article.id,
                enhancedContent,
                referenceUrls
            );

            if (updatedArticle) {
                console.log('Successfully published enhanced article!');
                console.log(`Article ID: ${updatedArticle.id}`);
                return updatedArticle;
            } else {
                console.error('Failed to publish enhanced article');
            }
        } catch (error) {
            console.error('Error in enhancement process:', error.message);
            console.error(error.stack);
        }
    }

    async fetchLatestArticle() {
        try {
            const response = await axios.get(`${this.laravelApiUrl}/api/articles/latest`);
            return response.data;
        } catch (error) {
            console.error('Error fetching latest article:', error.message);
            return null;
        }
    }

    async searchGoogle(query) {
        if (!this.googleApiKey || !this.googleCseId) {
            console.warn('Google API credentials not set. Using fallback search method...');
            return this.fallbackGoogleSearch(query);
        }

        try {
            const searchUrl = 'https://www.googleapis.com/customsearch/v1';
            const params = {
                key: this.googleApiKey,
                cx: this.googleCseId,
                q: query,
                num: 10, // Get more results to filter
            };

            const response = await axios.get(searchUrl, { params });
            
            if (!response.data.items) {
                return [];
            }

            // Filter for blog/article links
            const blogResults = response.data.items
                .filter(item => {
                    const link = item.link.toLowerCase();
                    // Filter out social media, PDFs, and non-article pages
                    return !link.includes('facebook.com') &&
                           !link.includes('twitter.com') &&
                           !link.includes('linkedin.com') &&
                           !link.includes('.pdf') &&
                           !link.includes('youtube.com') &&
                           (link.includes('/blog/') ||
                            link.includes('/article/') ||
                            link.includes('/post/') ||
                            link.includes('/news/') ||
                            link.match(/\/\d{4}\/\d{2}\//)); // Date-based URLs
                })
                .slice(0, 2)
                .map(item => ({
                    title: item.title,
                    link: item.link,
                    snippet: item.snippet,
                }));

            return blogResults;
        } catch (error) {
            console.error('Error searching Google:', error.message);
            return this.fallbackGoogleSearch(query);
        }
    }

    async fallbackGoogleSearch(query) {
        // Fallback: Use a simple web scraping approach
        // Note: This is a basic implementation and may not work reliably
        console.log('Using fallback search method...');
        
        try {
            const searchUrl = `https://www.google.com/search?q=${encodeURIComponent(query)}&num=10`;
            const response = await axios.get(searchUrl, {
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                },
            });

            const $ = cheerio.load(response.data);
            const results = [];

            $('div.g').each((i, elem) => {
                if (results.length >= 2) return false;

                const link = $(elem).find('a').attr('href');
                const title = $(elem).find('h3').text();

                if (link && title && link.startsWith('http')) {
                    // Filter for blog/article links
                    const linkLower = link.toLowerCase();
                    if (linkLower.includes('/blog/') ||
                        linkLower.includes('/article/') ||
                        linkLower.includes('/post/') ||
                        linkLower.includes('/news/')) {
                        results.push({
                            title: title,
                            link: link,
                        });
                    }
                }
            });

            return results;
        } catch (error) {
            console.error('Error in fallback search:', error.message);
            return [];
        }
    }

    async scrapeArticleContent(url) {
        try {
            const response = await axios.get(url, {
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                },
                timeout: 30000,
            });

            const $ = cheerio.load(response.data);

            // Remove script and style elements
            $('script, style, nav, footer, header, aside, .advertisement, .ads').remove();

            // Try multiple content selectors
            let content = '';

            const selectors = [
                'article',
                'article .content',
                'article .entry-content',
                '.post-content',
                '.article-content',
                'main article',
                'main .content',
            ];

            for (const selector of selectors) {
                const element = $(selector).first();
                if (element.length > 0) {
                    content = element.text();
                    if (content.length > 500) { // Ensure we got substantial content
                        break;
                    }
                }
            }

            // Fallback: get all paragraphs
            if (content.length < 500) {
                $('p').each((i, elem) => {
                    const text = $(elem).text().trim();
                    if (text.length > 50) {
                        content += text + '\n\n';
                    }
                });
            }

            return content.trim();
        } catch (error) {
            console.error(`Error scraping ${url}:`, error.message);
            return null;
        }
    }

    async enhanceWithLLM(originalArticle, referenceArticles) {
        if (!this.openai) {
            console.warn('OpenAI not configured. Using simple content merge...');
            return this.simpleContentMerge(originalArticle, referenceArticles);
        }

        try {
            const referenceTexts = referenceArticles.map(ref => 
                `Title: ${ref.title}\n\nContent: ${ref.content.substring(0, 2000)}...`
            ).join('\n\n---\n\n');

            const prompt = `You are an expert content writer. Your task is to update and enhance an article to match the formatting, style, and content quality of the reference articles provided.

Original Article:
Title: ${originalArticle.title}
Content: ${originalArticle.content.substring(0, 3000)}${originalArticle.content.length > 3000 ? '...' : ''}

Reference Articles (for style and formatting guidance):
${referenceTexts}

Please update the original article to:
1. Match the formatting and structure of the reference articles
2. Improve the content quality and readability
3. Maintain the core message and information from the original article
4. Use similar writing style and tone as the reference articles
5. Ensure proper paragraph structure and headings if applicable

Return only the enhanced article content, without any additional commentary or explanations.`;

            const completion = await this.openai.chat.completions.create({
                model: process.env.OPENAI_MODEL || 'gpt-4-turbo-preview',
                messages: [
                    {
                        role: 'system',
                        content: 'You are an expert content writer who enhances articles while maintaining their original message.',
                    },
                    {
                        role: 'user',
                        content: prompt,
                    },
                ],
                max_tokens: 4000,
                temperature: 0.7,
            });

            let enhancedContent = completion.choices[0].message.content;

            // Add citations at the bottom
            enhancedContent += '\n\n---\n\n## References\n\n';
            referenceArticles.forEach((ref, index) => {
                enhancedContent += `${index + 1}. [${ref.title}](${ref.url})\n\n`;
            });

            return enhancedContent;
        } catch (error) {
            console.error('Error enhancing with LLM:', error.message);
            return this.simpleContentMerge(originalArticle, referenceArticles);
        }
    }

    simpleContentMerge(originalArticle, referenceArticles) {
        // Fallback: Simple content merge without LLM
        let enhanced = originalArticle.content;
        
        enhanced += '\n\n---\n\n## References\n\n';
        referenceArticles.forEach((ref, index) => {
            enhanced += `${index + 1}. [${ref.title}](${ref.url})\n\n`;
        });

        return enhanced;
    }

    async publishEnhancedArticle(articleId, enhancedContent, referenceUrls) {
        try {
            const response = await axios.put(
                `${this.laravelApiUrl}/api/articles/${articleId}`,
                {
                    content: enhancedContent,
                    is_updated: true,
                    reference_articles: referenceUrls,
                }
            );

            return response.data;
        } catch (error) {
            console.error('Error publishing enhanced article:', error.message);
            if (error.response) {
                console.error('Response data:', error.response.data);
            }
            return null;
        }
    }
}

// Main execution
if (require.main === module) {
    const service = new ArticleEnhancementService();
    service.enhanceLatestArticle()
        .then(() => {
            console.log('Enhancement process completed');
            process.exit(0);
        })
        .catch((error) => {
            console.error('Fatal error:', error);
            process.exit(1);
        });
}

module.exports = ArticleEnhancementService;


