<?php

namespace JCFrane\MdBlog;

use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use JCFrane\MdBlog\Parsers\FrontMatterParser;
use JCFrane\MdBlog\Parsers\MarkdownParser;
use JCFrane\MdBlog\Support\ImageUrlResolver;
use JCFrane\MdBlog\Support\Text;
use Symfony\Component\Finder\Finder;

class PostRepository
{
    /**
     * Bump when the shape of a cached Post changes so stale entries are re-parsed
     * instead of unserialized with missing properties.
     */
    public const CACHE_VERSION = 2;

    /**
     * @param  class-string<Post>  $postClass
     */
    public function __construct(
        private readonly FrontMatterParser $frontMatterParser,
        private readonly MarkdownParser $markdownParser,
        private readonly CacheRepository $cache,
        private readonly string $path,
        private readonly bool $cacheEnabled,
        private readonly int $cacheTtl,
        private readonly string $postClass = Post::class,
        private readonly ?ImageUrlResolver $imageUrlResolver = null,
        private readonly int $excerptLength = 160,
        private readonly int $wordsPerMinute = 200,
    ) {}

    /**
     * Every post in the directory, drafts included, in filesystem order.
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        $directory = $this->resolvedPath();

        if (! is_dir($directory)) {
            return collect();
        }

        $posts = collect();

        foreach ($this->markdownFiles($directory) as $file) {
            $post = $this->loadPost($file->getRealPath());

            if ($post !== null) {
                $posts->push($post);
            }
        }

        $this->storeSlugIndex($posts);

        return $posts;
    }

    /**
     * Look a post up through the cached slug index, falling back to a full
     * scan when the index is cold or stale (file moved, slug changed).
     */
    public function findBySlug(string $slug): ?Post
    {
        if ($this->cacheEnabled) {
            $index = $this->cache->get($this->slugIndexKey());
            $filePath = is_array($index) ? ($index[$slug] ?? null) : null;

            if (is_string($filePath) && is_file($filePath)) {
                $post = $this->loadPost($filePath);

                if ($post !== null && $post->slug === $slug) {
                    return $post;
                }
            }
        }

        return $this->all()->first(fn (Post $post) => $post->slug === $slug);
    }

    public function findByPath(string $absolutePath): ?Post
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        return $this->loadPost($absolutePath);
    }

    public function clearCache(): void
    {
        $this->cache->forget($this->slugIndexKey());

        $directory = $this->resolvedPath();

        if (! is_dir($directory)) {
            return;
        }

        foreach ($this->markdownFiles($directory) as $file) {
            $this->cache->forget($this->cacheKey($file->getRealPath()));
        }
    }

    private function markdownFiles(string $directory): Finder
    {
        return (new Finder())
            ->files()
            ->name('*.md')
            ->in($directory);
    }

    private function loadPost(string $filePath): ?Post
    {
        if (! $this->cacheEnabled) {
            return $this->parsePost($filePath);
        }

        $key = $this->cacheKey($filePath);
        $cached = $this->cache->get($key);

        if (
            $cached instanceof Post
            && get_class($cached) === $this->postClass
            && $cached->lastModified === filemtime($filePath)
        ) {
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

        $slug = (string) ($matter['slug'] ?? pathinfo($filePath, PATHINFO_FILENAME));
        $title = (string) ($matter['title'] ?? $slug);
        $date = isset($matter['date']) ? Carbon::parse($matter['date']) : Carbon::createFromTimestamp(filemtime($filePath));
        $tags = (array) ($matter['tags'] ?? []);
        $category = (string) ($matter['category'] ?? '');
        $published = (bool) ($matter['published'] ?? true);

        $excerpt = isset($matter['excerpt']) && trim((string) $matter['excerpt']) !== ''
            ? (string) $matter['excerpt']
            : Text::excerpt($html, $this->excerptLength);

        $cover = isset($matter['cover']) && trim((string) $matter['cover']) !== ''
            ? $this->resolveCover((string) $matter['cover'])
            : null;

        $knownKeys = ['title', 'slug', 'date', 'tags', 'category', 'excerpt', 'cover', 'published'];
        $meta = array_diff_key($matter, array_flip($knownKeys));

        return $this->postClass::make([
            'title' => $title,
            'slug' => $slug,
            'date' => $date,
            'body' => $body,
            'html' => $html,
            'tags' => $tags,
            'category' => $category,
            'excerpt' => $excerpt,
            'cover' => $cover,
            'readingTime' => Text::readingTime($html, $this->wordsPerMinute),
            'published' => $published,
            'meta' => $meta,
            'filePath' => $filePath,
            'lastModified' => filemtime($filePath),
        ]);
    }

    private function resolveCover(string $cover): string
    {
        if ($this->imageUrlResolver === null) {
            return $cover;
        }

        return $this->imageUrlResolver->resolve($cover);
    }

    /**
     * @param  Collection<int, Post>  $posts
     */
    private function storeSlugIndex(Collection $posts): void
    {
        if (! $this->cacheEnabled) {
            return;
        }

        $index = $posts->mapWithKeys(fn (Post $post) => [$post->slug => $post->filePath])->all();

        $this->cache->put($this->slugIndexKey(), $index, $this->cacheTtl);
    }

    private function cacheKey(string $filePath): string
    {
        return 'md_blog_post_v' . self::CACHE_VERSION . '_' . md5($filePath);
    }

    private function slugIndexKey(): string
    {
        return 'md_blog_index_v' . self::CACHE_VERSION . '_' . md5($this->resolvedPath());
    }

    private function resolvedPath(): string
    {
        if (str_starts_with($this->path, '/')) {
            return $this->path;
        }

        return base_path($this->path);
    }
}
