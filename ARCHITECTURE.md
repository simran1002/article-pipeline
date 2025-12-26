# Architecture Documentation

## System Overview

This project implements a three-phase article management and enhancement system:

1. **Phase 1**: Laravel backend for scraping and storing articles
2. **Phase 2**: NodeJS service for enhancing articles using Google Search and LLM
3. **Phase 3**: React frontend for displaying articles

## Component Details

### Laravel Backend (`backend/`)

**Purpose**: API server and data storage

**Key Components**:
- `ArticleController`: Handles CRUD operations
- `ScrapeArticles` Command: Scrapes articles from BeyondChats blogs
- `Article` Model: Database model for articles
- Database: MySQL/PostgreSQL for persistent storage

**API Endpoints**:
- `GET /api/articles` - List all articles
- `GET /api/articles/{id}` - Get single article
- `GET /api/articles/latest` - Get latest article
- `POST /api/articles` - Create article
- `PUT /api/articles/{id}` - Update article
- `DELETE /api/articles/{id}` - Delete article

**Database Schema**:
```sql
articles:
  - id (primary key)
  - title
  - content (text)
  - slug (unique)
  - original_url
  - excerpt
  - author
  - published_at
  - is_updated (boolean)
  - reference_articles (JSON array)
  - created_at, updated_at
```

### NodeJS Service (`nodejs-service/`)

**Purpose**: Article enhancement service

**Workflow**:
1. Fetches latest article from Laravel API
2. Searches article title on Google
3. Filters results for blog/article links
4. Scrapes content from top 2 articles
5. Calls LLM API (OpenAI) to enhance article
6. Publishes enhanced article back to Laravel API

**Key Dependencies**:
- `axios`: HTTP client
- `cheerio`: HTML parsing and scraping
- `openai`: LLM API integration

**Configuration**:
- Requires Google API key and Custom Search Engine ID
- Requires OpenAI API key
- Connects to Laravel API

### React Frontend (`frontend/`)

**Purpose**: User interface for viewing articles

**Components**:
- `ArticleList`: Displays all articles in a grid
- `ArticleDetail`: Shows individual article with full content
- `App`: Main application component with routing

**Features**:
- Responsive design
- Badge indicators for original vs enhanced articles
- Reference links display
- Professional UI with modern styling

## Data Flow

```
┌─────────────────────────────────────────────────────────┐
│                    Initial Setup                        │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Laravel: php artisan articles:scrape                   │
│  - Scrapes BeyondChats blogs                            │
│  - Stores 5 oldest articles in database                 │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  React Frontend                                         │
│  - Fetches articles from Laravel API                    │
│  - Displays articles to user                           │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  NodeJS Service: node index.js                          │
│  - Fetches latest article                               │
│  - Google Search → Scrape → LLM → Update               │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Laravel API: PUT /api/articles/{id}                   │
│  - Updates article with enhanced content                │
│  - Sets is_updated = true                              │
│  - Stores reference URLs                                │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  React Frontend                                         │
│  - Shows updated article with "Enhanced" badge          │
│  - Displays reference links                            │
└─────────────────────────────────────────────────────────┘
```

## Technology Stack

### Backend
- **Framework**: Laravel 10 (minimal setup)
- **Language**: PHP 8.1+
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **HTTP Client**: Guzzle

### NodeJS Service
- **Runtime**: Node.js 18+
- **HTTP Client**: Axios
- **HTML Parsing**: Cheerio
- **LLM**: OpenAI API

### Frontend
- **Framework**: React 18
- **Routing**: React Router v6
- **HTTP Client**: Axios
- **Styling**: CSS3 with modern features

## Security Considerations

1. **API Keys**: All sensitive keys stored in `.env` files (not committed)
2. **CORS**: Configured for frontend-backend communication
3. **Input Validation**: Laravel validation on all API endpoints
4. **Error Handling**: Comprehensive error handling throughout
5. **Rate Limiting**: Can be enabled on Laravel API routes

## Scalability Notes

- Database can be scaled horizontally with read replicas
- NodeJS service can be containerized and scaled independently
- Frontend can be served via CDN
- API can be load balanced

## Future Enhancements

- Add authentication/authorization
- Implement caching layer (Redis)
- Add queue system for async article enhancement
- Implement webhook notifications
- Add article versioning/history
- Implement search functionality
- Add article categories/tags


