---
title: "Laravel 12: A First Look"
slug: laravel-12-first-look
date: 2026-02-20
tags: [laravel, php, release]
category: tutorials
excerpt: What's new in Laravel 12 and why it matters for your next project.
---

# Laravel 12: A First Look

Laravel 12 continues the framework's tradition of thoughtful, incremental improvements. Here's a quick rundown of what caught my attention.

## Starter Kits Overhaul

The biggest visible change is the new starter kit architecture. React, Vue, and Livewire kits now ship with a cleaner structure and better defaults out of the box.

## PHP 8.2+ Required

Laravel 12 drops support for PHP 8.1. If you haven't upgraded yet, now's the time:

```bash
# Check your version
php -v

# On macOS with Homebrew
brew install php@8.3
```

## Improved Testing Ergonomics

The test suite got some nice quality-of-life improvements:

```php
// Before
$this->actingAs($user)
    ->get('/dashboard')
    ->assertStatus(200);

// Now supports fluent expectations
$this->actingAs($user)
    ->get('/dashboard')
    ->assertOk()
    ->assertSeeText('Welcome back');
```

## Should You Upgrade?

If you're starting a new project, absolutely use Laravel 12. For existing apps, the upgrade path from 11 is straightforward — check the [upgrade guide](https://laravel.com/docs/12.x/upgrade) for the full list of changes.

## Summary

| Feature | Impact |
|---------|--------|
| New starter kits | High — better DX |
| PHP 8.2+ minimum | Medium — plan your upgrade |
| Testing improvements | Low — nice to have |

Laravel keeps getting better without getting bloated. That's hard to do.
