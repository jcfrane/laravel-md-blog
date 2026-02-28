<?php

namespace JCFrane\MdBlog;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use JCFrane\MdBlog\Parsers\FrontMatterParser;
use JCFrane\MdBlog\Parsers\MarkdownParser;
use Symfony\Component\Finder\Finder;

class PostRepository
{
    public function __construct(
        private readonly FrontMatterParser $frontMatterParser,
        private readonly MarkdownParser $markdownParser,
        private readonly CacheRepository $cache,
        private readonly string $path,
        private readonly bool $cacheEnabled,
        private readonly int $cacheTtl,
    ) {}

    /**
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        $directory = $this->resolvedPath();

        if (! is_dir($directory)) {
            return collect();
        }

        $finder = (new Finder())
            ->files()
            ->name('*.md')
            ->in($directory);

        $posts = collect();

        foreach ($finder as $file) {
            $post = $this->loadPost($file->getRealPath());

            if ($post !== null) {
                $posts->push($post);
            }
        }

        return $posts;
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->all()->first(fn (Post $post) => $post->slug === $slug);
    }

    public function clearCache(): void
    {
        $directory = $this->resolvedPath();

        if (! is_dir($directory)) {
            return;
        }

        $finder = (new Finder())
            ->files()
            ->name('*.md')
            ->in($directory);

        foreach ($finder as $file) {
            $this->cache->forget($this->cacheKey($file->getRealPath()));
        }
    }

    private function loadPost(string $filePath): ?Post
    {
        if (! $this->cacheEnabled) {
            return $this->parsePost($filePath);
        }

        $key = $this->cacheKey($filePath);
        $cached = $this->cache->get($key);

        if ($cached instanceof Post && $cached->lastModified === filemtime($filePath)) {
            return $cached;
        }

        $post = $this->parsePost($filePath);

        if ($post !== null) {
            $this->cache->put($key, $post, $this->cacheTtl);
        }

        return $post;
    }

    private function parsePost(string $filePath): ?Post
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        $parsed = $this->frontMatterParser->parse($content);
        $matter = $parsed['matter'];
        $body = $parsed['body'];
        $html = $this->markdownParser->toHtml($body);

        $slug = $matter['slug'] ?? pathinfo($filePath, PATHINFO_FILENAME);
        $title = $matter['title'] ?? $slug;
        $date = isset($matter['date']) ? Carbon::parse($matter['date']) : Carbon::createFromTimestamp(filemtime($filePath));
        $tags = $matter['tags'] ?? [];
        $category = $matter['category'] ?? '';
        $excerpt = $matter['excerpt'] ?? '';
        $published = $matter['published'] ?? true;

        $knownKeys = ['title', 'slug', 'date', 'tags', 'category', 'excerpt', 'published'];
        $meta = array_diff_key($matter, array_flip($knownKeys));

        return new Post(
            title: $title,
            slug: $slug,
            date: $date,
            body: $body,
            html: $html,
            tags: $tags,
            category: $category,
            excerpt: $excerpt,
            published: $published,
            meta: $meta,
            filePath: $filePath,
            lastModified: filemtime($filePath),
        );
    }

    private function cacheKey(string $filePath): string
    {
        return 'md_blog_post_' . md5($filePath);
    }

    private function resolvedPath(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return base_path($this->path);
    }
}
