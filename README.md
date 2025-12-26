# BeyondChats Assignment Submission

This repository contains a complete implementation of the BeyondChats assignment, including:
- **Laravel Backend**: CRUD APIs for article management
- **NodeJS Service**: Article enhancement service with Google search and LLM integration
- **React Frontend**: Responsive UI for displaying articles

## Project Structure

```
Beyondchat/
├── backend/          # Laravel API
├── nodejs-service/  # NodeJS article enhancement service
├── frontend/        # React frontend
└── README.md        # This file
```

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                      React Frontend                          │
│                      (Port 3000)                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ArticleList Component                               │   │
│  │  ArticleDetail Component                             │   │
│  └──────────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────────┘
                        │ HTTP REST API Calls
                        │ (GET /api/articles, etc.)
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                           │
│                      (Port 8000)                             │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ArticleController                                   │   │
│  │  - GET /api/articles (List)                          │   │
│  │  - GET /api/articles/{id} (Show)                     │   │
│  │  - POST /api/articles (Create)                       │   │
│  │  - PUT /api/articles/{id} (Update)                   │   │
│  │  - DELETE /api/articles/{id} (Delete)                │   │
│  │  - GET /api/articles/latest                          │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ScrapeArticles Command                              │   │
│  │  - Scrapes BeyondChats blogs                         │   │
│  │  - Stores articles in database                       │   │
│  └──────────────────────────────────────────────────────┘   │
│                        │                                     │
│                        ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │              MySQL/PostgreSQL Database                │   │
│  │  ┌────────────────────────────────────────────────┐  │   │
│  │  │  articles table                                │  │   │
│  │  │  - id, title, content, slug                    │  │   │
│  │  │  - original_url, excerpt, author               │  │   │
│  │  │  - is_updated, reference_articles               │  │   │
│  │  └────────────────────────────────────────────────┘  │   │
│  └──────────────────────────────────────────────────────┘   │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ Fetch Latest Article
                        │ (GET /api/articles/latest)
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                  NodeJS Enhancement Service                  │
│                      (Standalone)                           │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  1. Fetch Latest Article from Laravel API            │   │
│  └──────────────────────────────────────────────────────┘   │
│                        │                                     │
│                        ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  2. Google Search API                                │   │
│  │     - Search article title                           │   │
│  │     - Filter blog/article results                    │   │
│  └──────────────────────────────────────────────────────┘   │
│                        │                                     │
│                        ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  3. Web Scraping (Cheerio)                           │   │
│  │     - Scrape top 2 article links                     │   │
│  │     - Extract main content                           │   │
│  └──────────────────────────────────────────────────────┘   │
│                        │                                     │
│                        ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  4. LLM API (OpenAI)                                 │   │
│  │     - Enhance article content                        │   │
│  │     - Match formatting/style                         │   │
│  │     - Add citations                                  │   │
│  └──────────────────────────────────────────────────────┘   │
│                        │                                     │
│                        ▼                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  5. Publish Enhanced Article                         │   │
│  │     - PUT /api/articles/{id}                         │   │
│  │     - Set is_updated = true                          │   │
│  │     - Include reference_articles                     │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

## Data Flow

1. **Initial Scraping**: Laravel scraper fetches 5 oldest articles from BeyondChats blogs
2. **Article Storage**: Articles stored in MySQL/PostgreSQL database
3. **Enhancement Trigger**: NodeJS service fetches latest article from Laravel API
4. **Google Search**: Service searches article title on Google
5. **Content Scraping**: Fetches top 2 blog/article links and scrapes their content
6. **LLM Enhancement**: Calls LLM API to update article formatting and content
7. **Publication**: Updated article published via Laravel API with citations
8. **Frontend Display**: React app fetches and displays both original and updated articles

## Local Setup Instructions

### Prerequisites
- PHP 8.1+ with Composer
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 13+
- Git

### Backend Setup (Laravel)

1. Navigate to backend directory:
```bash
cd backend
```

2. Install dependencies:
```bash
composer install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=beyondchat
DB_USERNAME=root
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Seed initial articles (scrapes from BeyondChats):
```bash
php artisan articles:scrape
```

8. Start Laravel server:
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

### NodeJS Service Setup

1. Navigate to nodejs-service directory:
```bash
cd nodejs-service
```

2. Install dependencies:
```bash
npm install
```

3. Copy environment file:
```bash
cp .env.example .env
```

4. Configure environment variables in `.env`:
```env
LARAVEL_API_URL=http://localhost:8000
OPENAI_API_KEY=your_openai_api_key
GOOGLE_API_KEY=your_google_api_key
GOOGLE_CSE_ID=your_google_cse_id
```

5. Run the enhancement service:
```bash
npm start
```

Or run manually:
```bash
node index.js
```

### Frontend Setup (React)

1. Navigate to frontend directory:
```bash
cd frontend
```

2. Install dependencies:
```bash
npm install
```

3. Create `.env` file:
```env
REACT_APP_API_URL=http://localhost:8000
```

4. Start development server:
```bash
npm start
```

The frontend will be available at `http://localhost:3000`

## API Endpoints

### Articles
- `GET /api/articles` - List all articles
- `GET /api/articles/{id}` - Get single article
- `POST /api/articles` - Create article
- `PUT /api/articles/{id}` - Update article
- `DELETE /api/articles/{id}` - Delete article
- `GET /api/articles/latest` - Get latest article

## Features

### Phase 1: Laravel Backend
- ✅ Web scraper for BeyondChats blogs (5 oldest articles)
- ✅ Database storage with migrations
- ✅ Complete CRUD API endpoints
- ✅ Article model with relationships

### Phase 2: NodeJS Service
- ✅ Google Search integration
- ✅ Web scraping for top 2 articles
- ✅ LLM API integration (OpenAI)
- ✅ Article enhancement and formatting
- ✅ Citation management

### Phase 3: React Frontend
- ✅ Responsive, professional UI
- ✅ Article listing and detail views
- ✅ Display original and updated articles
- ✅ Modern design with Tailwind CSS

## Live Link

**Note**: Please deploy the frontend and update this section with the live URL.

To deploy:
1. Build the React app: `cd frontend && npm run build`
2. Deploy the `build` folder to your hosting service (Vercel, Netlify, etc.)
3. Ensure the backend API is accessible (update CORS settings if needed)
4. Update `REACT_APP_API_URL` in your deployment environment

Example deployment platforms:
- Frontend: Vercel, Netlify, GitHub Pages
- Backend: Heroku, DigitalOcean, AWS EC2
- NodeJS Service: Can run as a scheduled job or serverless function

## Notes

- The scraper respects rate limiting and includes proper error handling
- Google Search requires API key and Custom Search Engine ID
- LLM API requires OpenAI API key (or compatible service)
- All services include comprehensive error handling and logging

## Development Notes

- Partial completion is acceptable per assignment guidelines
- Code prioritizes functionality and maintainability
- Error handling implemented throughout
- Environment variables used for all sensitive data

## License

This code is the property of the developer and is submitted for evaluation purposes only.

