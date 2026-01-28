# Deployment Guide

This guide covers deploying the LEGO Storage API to **Railway** with **Turso** as the database.

## Why Railway + Turso?

- **Railway**: Accepts PayPal, $5 free credit/month, easy GitHub integration
- **Turso**: SQLite-compatible, generous free tier (9GB storage, 500M rows read/month)

## Prerequisites

- [Turso CLI](https://docs.turso.tech/cli/installation) installed
- Railway account (sign up at https://railway.app)
- Turso account (sign up at https://turso.tech)
- GitHub repository connected to Railway

## 1. Set Up Turso Database

```bash
# Login to Turso
turso auth login

# Create a database (choose a region close to Railway's region)
turso db create lego-storage --location ams

# Get the database URL
turso db show lego-storage --url
# Output: libsql://lego-storage-<your-username>.turso.io

# Create an auth token
turso db tokens create lego-storage
# Save this token securely
```

## 2. Set Up Railway

### Via Dashboard (Recommended)

1. Go to https://railway.app and sign in
2. Click **"New Project"** → **"Deploy from GitHub repo"**
3. Select the `Goosterhof/lego-storage` repository
4. Railway will auto-detect the Dockerfile

### Via CLI (Alternative)

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Initialize project
railway init

# Link to existing project (if already created via dashboard)
railway link
```

## 3. Configure Environment Variables

In the Railway dashboard, go to your service → **Variables** tab and add:

| Variable | Value |
|----------|-------|
| `APP_NAME` | `LEGO Storage` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | `base64:...` (generate with `php artisan key:generate --show`) |
| `APP_URL` | `https://your-app.up.railway.app` (Railway provides this) |
| `DB_CONNECTION` | `libsql` |
| `DB_URL` | `libsql://lego-storage-your-username.turso.io` |
| `TURSO_AUTH_TOKEN` | Your Turso token |
| `TURSO_REMOTE_ONLY` | `true` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `REBRICKABLE_API_KEY` | Your Rebrickable API key |

### Generate APP_KEY locally

```bash
php artisan key:generate --show
# Copy the output: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

## 4. Deploy

Railway deploys automatically when you push to your connected branch. You can also:

- **Manual deploy**: Click "Deploy" in the Railway dashboard
- **CLI deploy**: Run `railway up`

### First Deployment

The first deploy may take a few minutes as it builds the Docker image. Subsequent deploys are faster due to layer caching.

## 5. Run Migrations

Migrations run automatically on each deploy (configured in `railway.json`). To run manually:

```bash
# Via Railway CLI
railway run php artisan migrate --force

# Or via the Railway shell
railway shell
php artisan migrate --force
```

## 6. Get Your App URL

After deployment, Railway provides a URL like:
- `https://lego-storage-production.up.railway.app`

You can also add a custom domain in **Settings** → **Domains**.

## Useful Commands

```bash
# View logs
railway logs

# Open a shell in the container
railway shell

# Run artisan commands
railway run php artisan migrate:status
railway run php artisan tinker

# View environment variables
railway variables

# Open the app in browser
railway open
```

## Monitoring

- **Railway Dashboard**: https://railway.app/dashboard
- **Turso Dashboard**: https://turso.tech/app

## Cost Breakdown (Free Tier)

### Railway Free Tier
- $5 credit per month
- 512MB RAM, 1 vCPU
- 1GB disk
- Enough for low-traffic personal projects

### Turso Free Tier
- 9GB total storage
- 500 million rows read per month
- 25 million rows written per month
- Unlimited databases

## Troubleshooting

### Build Fails

Check the build logs in Railway dashboard. Common issues:
- Missing environment variables
- Dockerfile syntax errors

### Database Connection Issues

```bash
# Test connection via Railway shell
railway shell
php artisan tinker --execute="DB::connection()->getPdo();"
```

### Clear Caches

```bash
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan route:clear
```

### View Laravel Logs

```bash
railway logs
```

## Local Development with Turso (Optional)

You can use Turso locally with embedded replicas:

```env
DB_CONNECTION=libsql
DB_URL=file:database/database.sqlite
TURSO_SYNC_URL=libsql://lego-storage-your-username.turso.io
TURSO_AUTH_TOKEN=your-token
TURSO_REMOTE_ONLY=false
```

This syncs a local SQLite file with your Turso database.

---

## Alternative: Fly.io Deployment

If you prefer Fly.io (requires credit card), the `fly.toml` configuration is included. See the [Fly.io docs](https://fly.io/docs/laravel/) for setup instructions.
