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

### Email

Send blog posts as emails to your subscribers. The feature is opt-in and disabled by default.

#### Setup

1. Set the environment variables:

```env
MD_BLOG_MAIL_ENABLED=true
MD_BLOG_MAIL_RECIPIENT_MODEL=App\Models\User
```

2. Implement the `EmailRecipient` interface on your model:

```php
use JCFrane\MdBlog\Contracts\EmailRecipient;
use JCFrane\MdBlog\Traits\ReceivesPostMail;
use JCFrane\MdBlog\Post;

class User extends Authenticatable implements EmailRecipient
{
    use ReceivesPostMail;

    public function shouldReceivePostEmail(Post $post): bool
    {
        return $this->subscribed_to_newsletter;
    }
}
```

The `ReceivesPostMail` trait provides defaults for `emailRecipients()` (returns `static::cursor()`), `getEmailAddress()` (`$this->email`), and `getEmailName()` (`$this->name`). Override any of these if your model differs.

#### Sending

Three ways to send:

```bash
# Artisan command — single post or entire directory
php artisan md-blog:send-mail resources/markdown/blog/my-post.md
php artisan md-blog:send-mail resources/markdown/blog/
```

```php
// Facade
MdBlog::sendPost('resources/markdown/blog/my-post.md');
```

```bash
# HTTP route (POST /{route_prefix}/send-mail, protected by auth middleware)
curl -X POST /md-blog/send-mail -d '{"path": "resources/markdown/blog/my-post.md"}'
```

#### Queue Support

Enable queued sending to avoid blocking:

```env
MD_BLOG_MAIL_QUEUE=true
MD_BLOG_MAIL_QUEUE_CONNECTION=redis
MD_BLOG_MAIL_QUEUE_NAME=emails
```

#### Customizing the Email Template

Publish the views to customize the email layout:

```bash
php artisan vendor:publish --tag=md-blog-views
```

This copies the template to `resources/views/vendor/md-blog/mail/post.blade.php`.

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

    'mail' => [
        'enabled'          => env('MD_BLOG_MAIL_ENABLED', false),
        'recipient_model'  => env('MD_BLOG_MAIL_RECIPIENT_MODEL', null),
        'queue'            => env('MD_BLOG_MAIL_QUEUE', false),
        'queue_connection' => env('MD_BLOG_MAIL_QUEUE_CONNECTION', null),
        'queue_name'       => env('MD_BLOG_MAIL_QUEUE_NAME', null),
        'chunk_size'       => env('MD_BLOG_MAIL_CHUNK_SIZE', 50),
        'middleware'       => ['auth'],
        'subject_prefix'   => env('MD_BLOG_MAIL_SUBJECT_PREFIX', ''),
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
