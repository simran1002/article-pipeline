# Quick Start Guide

Get up and running in 5 minutes!

## Prerequisites Check

```bash
php -v        # Need 8.1+
composer -v   # Need installed
node -v       # Need 18+
npm -v        # Need installed
mysql -v      # Need 8.0+
```

## 1. Database Setup (30 seconds)

```bash
mysql -u root -p
CREATE DATABASE beyondchat;
EXIT;
```

## 2. Backend Setup (2 minutes)

```bash
cd backend
composer install
cp .env.example .env
# Edit .env: Set DB_DATABASE=beyondchat, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate
php artisan articles:scrape
php artisan serve
```

✅ Backend running at http://localhost:8000

## 3. NodeJS Service Setup (1 minute)

```bash
cd ../nodejs-service
npm install
cp .env.example .env
# Edit .env: Add OPENAI_API_KEY, GOOGLE_API_KEY, GOOGLE_CSE_ID
# Set LARAVEL_API_URL=http://localhost:8000
```

**Optional**: Run enhancement service:
```bash
node index.js
```

## 4. Frontend Setup (1 minute)

```bash
cd ../frontend
npm install
# Create .env file: REACT_APP_API_URL=http://localhost:8000
npm start
```

✅ Frontend running at http://localhost:3000

## Verify Everything Works

1. **Backend**: Visit http://localhost:8000/api/articles
   - Should return JSON array of articles

2. **Frontend**: Visit http://localhost:3000
   - Should show article list

3. **Enhancement**: Run `node nodejs-service/index.js`
   - Should enhance the latest article
   - Check frontend - article should show "Enhanced" badge

## Troubleshooting

**Backend not working?**
- Check database connection in `.env`
- Ensure database exists
- Run `php artisan migrate` again

**Frontend can't connect?**
- Check backend is running on port 8000
- Verify `REACT_APP_API_URL` in frontend `.env`

**NodeJS service fails?**
- Check API keys in `.env`
- Ensure backend is running
- Check Laravel API returns articles

## Next Steps

- Deploy frontend to get live link
- Set up scheduled job for automatic enhancement
- Customize UI styling
- Add more features!


