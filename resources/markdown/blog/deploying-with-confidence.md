---
title: Deploying Laravel Apps with Confidence
slug: deploying-with-confidence
date: 2026-02-15
tags: [laravel, devops, deployment]
category: guides
excerpt: A practical checklist for deploying Laravel applications without the anxiety.
---

# Deploying Laravel Apps with Confidence

Deployments don't have to be stressful. Here's the checklist I follow every time.

## Before You Deploy

- [ ] All tests pass locally
- [ ] Environment variables are set on the server
- [ ] Database migrations are tested against a copy of production data
- [ ] Assets are compiled and cache-busted

## The Deploy Script

A minimal zero-downtime deploy looks something like this:

```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart workers
php artisan queue:restart
```

## Things People Forget

### 1. Queue Workers

If you're using queues, restarting workers after deploy is critical. Old workers run old code.

### 2. Scheduler Changes

If you changed scheduled tasks, verify they're registered:

```bash
php artisan schedule:list
```

### 3. Config Cache

If you added new config values, `config:cache` must run *after* your `.env` is updated. Order matters.

## Rollback Plan

Always know how to roll back:

```bash
# Revert to previous commit
git checkout HEAD~1

# Roll back the last migration
php artisan migrate:rollback --step=1
```

Keep it simple. Keep it repeatable. Deploy often so each deploy is small.
