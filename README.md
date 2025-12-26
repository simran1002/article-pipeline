# BeyondChats Assignment

Full-stack application for scraping, managing, and enhancing articles from BeyondChats blogs.

## Project Structure

```
Beyondchat/
├── backend/          # Laravel API with CRUD operations
├── nodejs-service/  # Article enhancement service
└── frontend/        # React frontend application
```

## Features

- Scrape articles from BeyondChats blogs
- CRUD API for article management
- Article enhancement using Google search and LLM
- Responsive React frontend

## Setup

### Backend (Laravel)

```bash
cd backend
composer install
cp env.template .env
php run-migrations.php
php scrape-articles.php
php -S localhost:8000 -t public router.php
```

### Frontend (React)

```bash
cd frontend
npm install
npm start
```

### NodeJS Service

```bash
cd nodejs-service
npm install
node index.js
```

## API Endpoints

- GET /api/articles - List all articles
- GET /api/articles/{id} - Get single article
- GET /api/articles/latest - Get latest article
- POST /api/articles - Create article
- PUT /api/articles/{id} - Update article
- DELETE /api/articles/{id} - Delete article

## Database

Uses SQLite by default. Database file: `backend/database/database.sqlite`

To use MySQL, update `.env` file in backend directory with your database credentials.

## Environment Variables

Backend: Create `.env` file from `env.template`
NodeJS: Create `.env` file with `OPENAI_API_KEY` and `GOOGLE_API_KEY` (optional)
Frontend: Set `REACT_APP_API_URL` if backend is not on localhost:8000

## License

MIT
