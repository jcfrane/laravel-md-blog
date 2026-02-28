---
title: Getting Started with laravel-md-blog
slug: getting-started
date: 2026-02-28
tags: [laravel, markdown, tutorial]
category: tutorials
excerpt: Learn how to use the laravel-md-blog package to power your blog with markdown files.
---

# Getting Started with laravel-md-blog

Welcome! This package lets you write blog posts as markdown files and query them with a clean API.

## Installation

```bash
composer require jcfrane/laravel-md-blog
```

## Writing Posts

Create `.md` files in `resources/markdown/blog/` with YAML front matter:

```yaml
---
title: My First Post
tags: [laravel, php]
category: tutorials
---
```

## Querying Posts

```php
use JCFrane\MdBlog\Facades\MdBlog;

$posts = MdBlog::latest();
$post = MdBlog::find('my-post-slug');
```

That's it! No database required.
