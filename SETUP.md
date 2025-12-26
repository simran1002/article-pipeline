# Quick Setup Guide

This is a quick reference guide for setting up the BeyondChats assignment project.

## Prerequisites Check

```bash
# Check PHP version (need 8.1+)
php -v

# Check Composer
composer --version

# Check Node.js (need 18+)
node -v
npm -v

# Check MySQL
mysql --version
```

## Step-by-Step Setup

### 1. Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE beyondchat;
EXIT;
```

### 2. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
# Edit .env with your database credentials
php artisan key:generate
php artisan migrate
php artisan articles:scrape
php artisan serve
```

### 3. NodeJS Service

```bash
cd nodejs-service
npm install
cp .env.example .env
# Edit .env with your API keys
node index.js
```

### 4. Frontend (React)

```bash
cd frontend
npm install
# Create .env file with REACT_APP_API_URL=http://localhost:8000
npm start
```

## Testing the Setup

1. **Backend**: Visit `http://localhost:8000/api/articles` - should return JSON
2. **Frontend**: Visit `http://localhost:3000` - should show article list
3. **NodeJS Service**: Run `node index.js` - should enhance the latest article

## Troubleshooting

### Backend Issues
- **Composer not found**: Install Composer from https://getcomposer.org/
- **Database connection error**: Check `.env` database credentials
- **Migration error**: Ensure database exists and user has permissions

### NodeJS Issues
- **Module not found**: Run `npm install` in nodejs-service directory
- **API key errors**: Check `.env` file has correct keys
- **Google Search fails**: Ensure Google API key and CSE ID are set

### Frontend Issues
- **Cannot connect to API**: Ensure backend is running on port 8000
- **CORS errors**: Check Laravel CORS configuration
- **Build errors**: Try deleting `node_modules` and reinstalling

## Environment Variables Checklist

### Backend (.env)
- [ ] DB_CONNECTION=mysql
- [ ] DB_HOST=127.0.0.1
- [ ] DB_DATABASE=beyondchat
- [ ] DB_USERNAME=root
- [ ] DB_PASSWORD=your_password

### NodeJS Service (.env)
- [ ] LARAVEL_API_URL=http://localhost:8000
- [ ] OPENAI_API_KEY=your_key
- [ ] GOOGLE_API_KEY=your_key
- [ ] GOOGLE_CSE_ID=your_id

### Frontend (.env)
- [ ] REACT_APP_API_URL=http://localhost:8000


