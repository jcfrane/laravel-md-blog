# laravel-md-blog

Write blog posts as markdown files with YAML front matter. No database, no CMS — just files and a clean query API.

Built for Laravel 12+ / PHP 8.2+.

## Installation

```bash
composer require jcfrane/laravel-md-blog
```

Publish the config (optional):

```bash
php artisan vendor:publish --tag=md-blog-config
```

## Usage

### Writing Posts

Create `.md` files in `resources/markdown/blog/` (configurable) with YAML front matter:

```markdown
---
title: My First Post
slug: my-first-post
date: 2026-02-28
tags: [laravel, php]
category: tutorials
excerpt: A quick intro to my blog.
published: true
---

# My First Post

Your markdown content here.
```

**Front matter fields:**

| Field | Required | Default |
|-------|----------|---------|
| `title` | No | Filename |
| `slug` | No | Filename |
| `date` | No | File modification time |
| `tags` | No | `[]` |
| `category` | No | `""` |
| `excerpt` | No | `""` |
| `published` | No | `true` |

Any additional front matter fields are available via `$post->meta`.

### Querying Posts

```php
use JCFrane\MdBlog\Facades\MdBlog;

// All published posts
$posts = MdBlog::all();

// Single post by slug
$post = MdBlog::find('my-first-post');

// Filter by tag
$posts = MdBlog::whereTag('laravel');

// Filter by category
$posts = MdBlog::whereCategory('tutorials');

// Published posts, newest first
$posts = MdBlog::latest();

// Clear the cache
MdBlog::clearCache();
```

All query methods return Laravel Collections (except `find()` which returns a `Post` or `null`). Drafts (`published: false`) are automatically excluded.

### The Post Object

Each post is an immutable `Post` DTO with these properties:

```php
$post->title;        // string
$post->slug;         // string
$post->date;         // Carbon instance
$post->body;         // raw markdown
$post->html;         // rendered HTML
$post->tags;         // array
$post->category;     // string
$post->excerpt;      // string
$post->published;    // bool
$post->meta;         // array (extra front matter fields)
$post->filePath;     // string
$post->lastModified; // int (unix timestamp)
```

`Post` implements `Arrayable`, `Jsonable`, and `JsonSerializable` for clean Inertia/API integration:

```php
// In an Inertia controller
return Inertia::render('Blog/Show', [
    'post' => MdBlog::find($slug),
]);
```

### Images

The package automatically handles images stored within your blog directory.

#### Relative Images
If you have an image at `resources/markdown/blog/images/photo.png`, you can reference it in your markdown as:

```markdown
![My Photo](images/photo.png)
```

The package will automatically rewrite this URL to a dedicated route that serves the image securely.

#### Image Configuration
By default, images are served with a long cache duration (24 hours). You can customize this in the config:

```php
'images' => [
    'enabled'   => true,
    'cache_ttl' => 86400, // in seconds
],
```

When `images.enabled` is `true` (default), the package registers a route at `/{route_prefix}/images/{path}` to serve your assets.

## Configuration

```php
// config/md-blog.php
return [
    'path' => env('MD_BLOG_PATH', 'resources/markdown/blog'),

    'cache' => [
        'enabled' => env('MD_BLOG_CACHE_ENABLED', true),
        'store'   => env('MD_BLOG_CACHE_STORE', null), // null = app default
        'ttl'     => env('MD_BLOG_CACHE_TTL', 3600),
    ],

    'images' => [
        'enabled'   => env('MD_BLOG_IMAGES_ENABLED', true),
        'cache_ttl' => env('MD_BLOG_IMAGES_CACHE_TTL', 86400),
    ],

    'commonmark' => [
        'html_input'         => 'strip',
        'allow_unsafe_links' => false,
    ],
];
```

## Caching

Posts are cached per-file with automatic staleness detection — when a file is modified, the cached entry is automatically refreshed on the next read. Use `MdBlog::clearCache()` to force a full cache bust.

## Development

### Running Tests

```bash
composer install
./vendor/bin/phpunit
```

### Workbench (Demo App)

The package includes a workbench powered by Orchestra Testbench:

```bash
php vendor/bin/testbench serve
```

Visit `http://localhost:8000/blog` to see the demo blog.

## License

MIT
