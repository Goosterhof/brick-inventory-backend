# Deployment Guide

This guide covers deploying the LEGO Storage API to **Fly.io** with **Turso** as the database.

## Prerequisites

- [Fly CLI](https://fly.io/docs/hands-on/install-flyctl/) installed
- [Turso CLI](https://docs.turso.tech/cli/installation) installed
- Fly.io account (free tier available)
- Turso account (free tier: 9GB storage, 500M rows read/month)

## 1. Set Up Turso Database

```bash
# Login to Turso
turso auth login

# Create a database (choose a region close to your Fly.io region)
turso db create lego-storage --location ams

# Get the database URL
turso db show lego-storage --url
# Output: libsql://lego-storage-<your-username>.turso.io

# Create an auth token
turso db tokens create lego-storage
# Save this token securely
```

## 2. Set Up Fly.io

```bash
# Login to Fly.io
fly auth login

# Launch the app (from project root)
fly launch --no-deploy

# When prompted:
# - App name: lego-storage (or your preferred name)
# - Region: ams (or match your Turso region)
# - Don't set up PostgreSQL or Redis
```

### Update fly.toml

If you changed the app name, update the `app` field in `fly.toml`:

```toml
app = "your-app-name"
```

## 3. Configure Secrets

Set the required environment variables as Fly.io secrets:

```bash
# Generate a new app key
php artisan key:generate --show
# Copy the output (base64:xxx...)

# Set secrets on Fly.io
fly secrets set APP_KEY="base64:your-generated-key"
fly secrets set APP_URL="https://your-app-name.fly.dev"
fly secrets set DB_CONNECTION="libsql"
fly secrets set DB_URL="libsql://lego-storage-your-username.turso.io"
fly secrets set TURSO_AUTH_TOKEN="your-turso-token"
fly secrets set TURSO_REMOTE_ONLY="true"
fly secrets set REBRICKABLE_API_KEY="your-rebrickable-key"
```

## 4. Deploy

```bash
# Deploy the application
fly deploy

# Check deployment status
fly status

# View logs
fly logs
```

## 5. Run Migrations

Migrations run automatically on deploy via the `release_command` in `fly.toml`. To run manually:

```bash
fly ssh console -C "php artisan migrate --force"
```

## Useful Commands

```bash
# Open a shell in the running container
fly ssh console

# View application logs
fly logs

# Check app status
fly status

# Scale the app (if needed)
fly scale count 1
fly scale memory 512

# Open the app in browser
fly open

# View secrets (names only)
fly secrets list
```

## Monitoring

- **Fly.io Dashboard**: https://fly.io/dashboard
- **Turso Dashboard**: https://turso.tech/app

## Cost Breakdown (Free Tier)

### Fly.io Free Tier
- 3 shared-cpu-1x VMs with 256MB RAM
- 3GB persistent storage
- 160GB outbound bandwidth

### Turso Free Tier
- 9GB total storage
- 500 million rows read per month
- 25 million rows written per month
- Unlimited databases

**Note**: The `fly.toml` is configured with `auto_stop_machines = "stop"` which stops the machine when idle, helping stay within free tier limits.

## Troubleshooting

### Database Connection Issues

```bash
# Test database connection
fly ssh console -C "php artisan tinker --execute=\"DB::connection()->getPdo();\""
```

### Check Environment Variables

```bash
fly ssh console -C "php artisan env"
```

### Clear Caches

```bash
fly ssh console -C "php artisan config:clear && php artisan cache:clear"
```

### View Error Logs

```bash
fly logs --app your-app-name
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
