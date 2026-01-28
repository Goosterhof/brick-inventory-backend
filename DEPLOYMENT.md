# Deployment Guide

This guide covers deploying the LEGO Storage API to **Railway** with **PostgreSQL**.

## Why Railway?

- Accepts PayPal (no credit card required)
- $5 free credit per month
- Easy GitHub integration with auto-deploy
- Built-in PostgreSQL database
- Simple environment variable management

## Prerequisites

- Railway account (sign up at https://railway.app)
- GitHub repository

## 1. Create Railway Project

### Via Dashboard

1. Go to https://railway.app and sign in
2. Click **"New Project"**
3. Select **"Deploy from GitHub repo"**
4. Choose the `Goosterhof/lego-storage` repository
5. Railway will auto-detect the Dockerfile

## 2. Add PostgreSQL Database

1. In your Railway project, click **"New"** → **"Database"** → **"Add PostgreSQL"**
2. Railway automatically creates the database and sets the `DATABASE_URL` variable
3. The app will automatically connect using this URL

## 3. Configure Environment Variables

In the Railway dashboard, go to your **web service** (not the database) → **Variables** tab.

### Required Variables

Click **"New Variable"** and add each of these:

| Variable | Value |
|----------|-------|
| `APP_NAME` | `LEGO Storage` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` (see below) |
| `APP_URL` | `https://your-app.up.railway.app` |
| `DB_CONNECTION` | `pgsql` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `REBRICKABLE_API_KEY` | Your Rebrickable API key |

### Generate APP_KEY

Run this locally to generate the key:

```bash
php artisan key:generate --show
```

Copy the output (starts with `base64:`) and paste it as the `APP_KEY` value.

### Link Database URL

Railway provides `DATABASE_URL` from your PostgreSQL service. To make it available to your app:

1. Click **"New Variable"**
2. Click **"Add Reference"**
3. Select your PostgreSQL service
4. Choose `DATABASE_URL`

This links the database connection string to your app.

## 4. Deploy

Railway deploys automatically when you push to your connected branch.

### Manual Deploy

Click **"Deploy"** in the Railway dashboard, or use the CLI:

```bash
npm install -g @railway/cli
railway login
railway up
```

## 5. Get Your App URL

After deployment:

1. Go to your web service → **Settings** → **Networking**
2. Click **"Generate Domain"** to get a public URL
3. Update your `APP_URL` environment variable with this URL

Your app will be available at something like:
`https://lego-storage-production.up.railway.app`

## Useful Commands

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link to project
railway link

# View logs
railway logs

# Open shell
railway shell

# Run artisan commands
railway run php artisan migrate:status
railway run php artisan tinker
```

## Cost Breakdown

### Railway Free Tier ($5 credit/month)
- 512MB RAM, 1 vCPU
- 1GB disk
- Includes PostgreSQL
- Enough for low-traffic personal projects

### Estimated Usage
- Small Laravel app: ~$2-3/month
- PostgreSQL: ~$1-2/month
- **Total**: Usually stays within free tier for personal use

## Troubleshooting

### Build Fails

Check build logs in Railway dashboard. Common issues:
- Missing `APP_KEY` - generate and add it
- Docker build errors - check Dockerfile syntax

### Database Connection Issues

1. Verify `DATABASE_URL` is linked from PostgreSQL service
2. Verify `DB_CONNECTION=pgsql` is set
3. Check logs: `railway logs`

### 500 Errors

```bash
# Check Laravel logs
railway logs

# Clear caches
railway run php artisan config:clear
railway run php artisan cache:clear
```

### Migrations Not Running

Migrations run automatically on deploy. To run manually:

```bash
railway run php artisan migrate --force
```

## Local Development

For local development, continue using SQLite:

```env
DB_CONNECTION=sqlite
```

The app will use `database/database.sqlite` locally.

---

## Alternative: Fly.io

If you have a credit card and prefer Fly.io, the `fly.toml` configuration is included. See the [Fly.io Laravel docs](https://fly.io/docs/laravel/) for setup.
