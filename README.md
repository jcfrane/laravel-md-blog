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
| `excerpt` | No | Generated from the body (see below) |
| `cover` | No | `null` |
| `published` | No | `true` |

Any additional front matter fields are available via `$post->meta`.

**Excerpt.** When `excerpt` is omitted, one is built from the first `excerpt_length` characters (default 160) of the body's prose. Headings and code blocks are skipped, and the cut lands on a word boundary.

**Cover.** `cover` works like an image in the body: a path relative to the blog directory (`images/cover.jpg`) is rewritten to the image route, while absolute URLs are kept as they are. The resolved URL is on `$post->cover`.

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

// Paginated, newest first (page read from the request like Eloquent)
$posts = MdBlog::paginate(9);

// Clear the cache
MdBlog::clearCache();
```

All query methods return Laravel Collections (except `find()` which returns a `Post` or `null`, and `paginate()` which returns a `LengthAwarePaginator`). Results are newest first. Drafts (`published: false`) are automatically excluded.

### Query Builder

For anything beyond a single filter, chain conditions on a query:

```php
MdBlog::query()
    ->whereTag(['sleep', 'focus'])      // any of these tags
    ->whereAllTags(['sleep', 'science']) // every one of these tags
    ->whereCategory('guides')            // string or array
    ->whereMeta('author', 'JC')          // extra front matter; one arg checks the key exists
    ->whereYear(2026)
    ->search('white noise')              // case-insensitive over title, excerpt, tags, body
    ->where(fn (Post $post) => $post->readingTime <= 5)
    ->latest()                           // or ->oldest(), ->orderBy('title'), ->orderBy(fn ($p) => ..., 'desc')
    ->skip(10)->take(5)
    ->get();                             // ->first(), ->count(), ->exists()
```

#### Pagination

`paginate()` returns a `LengthAwarePaginator`, so it renders in Blade with `{{ $posts->links() }}` and serializes for Inertia as `{ data, links, meta... }` the same way an Eloquent paginator does:

```php
return Inertia::render('Blog/Index', [
    'posts' => MdBlog::query()->whereTag($tag)->paginate(9),
]);
```

The current page is read from the request's `page` query string. Pass a different name or an explicit page with `paginate(9, 'p', $page)`.

#### Previous, Next and Related

```php
$post = MdBlog::find($slug);

MdBlog::previous($post); // older post or null
MdBlog::next($post);     // newer post or null
MdBlog::related($post);  // up to 3 posts sharing tags or category, best match first
MdBlog::related($post, 6);
```

Related posts are scored by shared tags (two points each) plus one point for the same category. Posts with no overlap are left out.

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
$post->excerpt;      // string (front matter or generated)
$post->cover;        // string|null (resolved URL)
$post->readingTime;  // int, minutes
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

### Custom Post Model

You can extend the base `Post` class to customize how posts are built — for example, to apply syntax highlighting to code blocks.

Set the `post_class` config option to your custom class:

```php
// config/md-blog.php
'post_class' => App\Blog\CustomPost::class,
```

Then create a class that extends `Post` and overrides the `make()` factory method:

```php
namespace App\Blog;

use JCFrane\MdBlog\Post;

class CustomPost extends Post
{
    public static function make(array $attributes): static
    {
        // Transform the rendered HTML before readonly properties are set
        $attributes['html'] = \Spatie\ShikiPhp\Shiki::highlight($attributes['html']);

        return parent::make($attributes);
    }
}
```

The `make()` method receives all post attributes as an array. You can modify any of them (`html`, `title`, `body`, etc.) before calling `parent::make()` to construct the post.

Cached posts built with a different class are re-parsed automatically, so no cache clear is needed after changing `post_class`.

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

#### Absolute URLs (APIs, mobile apps, email)

Image URLs are root-relative by default (`/md-blog/images/photo.png`). That breaks as soon as the HTML leaves your web page: a mobile app reading the JSON API or an email client has no origin to resolve against. Set the base URL and every image and cover URL becomes absolute:

```env
MD_BLOG_URL=https://huniapp.io
```

Rendered HTML is cached, so the base URL is baked in at parse time. Run `MdBlog::clearCache()` after changing it.

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

    'route_prefix' => env('MD_BLOG_ROUTE_PREFIX', 'md-blog'),

    // Base URL for image and cover URLs; null keeps them root-relative.
    'url' => env('MD_BLOG_URL', null),

    'post_class' => null, // e.g. App\Blog\CustomPost::class

    'excerpt_length'   => env('MD_BLOG_EXCERPT_LENGTH', 160),
    'words_per_minute' => env('MD_BLOG_WORDS_PER_MINUTE', 200),

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

Posts are cached per-file with automatic staleness detection — when a file is modified, the cached entry is automatically refreshed on the next read. A slug index is cached alongside so `find()` opens one file instead of scanning the directory; it rebuilds itself when a file is added, moved, removed or its slug changes. Use `MdBlog::clearCache()` to force a full cache bust.

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
