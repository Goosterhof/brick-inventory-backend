# Deployment Guide

This guide covers deploying the LEGO Storage API to **Railway** with **PostgreSQL**.

## Why Railway?

- Accepts PayPal (no credit card required)
- $5 free credit per month
- Auto-detects Laravel and configures PHP-FPM + nginx
- Built-in PostgreSQL database

## 1. Create Railway Project

1. Go to https://railway.app and sign in
2. Click **"New Project"** → **"Deploy from GitHub repo"**
3. Select your repository
4. Railway auto-detects Laravel and builds with Nixpacks

## 2. Add PostgreSQL Database

1. In your Railway project, click **"New"** → **"Database"** → **"Add PostgreSQL"**
2. Railway automatically creates the database

## 3. Configure Environment Variables

Go to your web service → **Variables** tab and add:

| Variable | Value |
|----------|-------|
| `APP_NAME` | `LEGO Storage` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate --show` |
| `APP_URL` | `https://your-app.up.railway.app` |
| `DB_CONNECTION` | `pgsql` |
| `DATABASE_URL` | `${{Postgres.DATABASE_URL}}` (click "Add Reference") |
| `LOG_CHANNEL` | `stderr` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `REBRICKABLE_API_KEY` | Your API key |

## 4. Deploy

Railway deploys automatically when you push. After first deploy:

1. Go to **Settings** → **Networking** → **Generate Domain**
2. Update `APP_URL` with your new domain

## Troubleshooting

### View Logs
Click on your deployment to see build and runtime logs.

### Run Migrations Manually
Use Railway's shell or CLI:
```bash
railway run php artisan migrate --force
```

### Clear Caches
```bash
railway run php artisan config:clear
railway run php artisan cache:clear
```

## Local Development

Keep using SQLite locally:
```env
DB_CONNECTION=sqlite
```
