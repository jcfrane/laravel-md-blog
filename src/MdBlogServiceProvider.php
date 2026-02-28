<?php

namespace JCFrane\MdBlog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use JCFrane\MdBlog\Parsers\FrontMatterParser;
use JCFrane\MdBlog\Parsers\MarkdownParser;

class MdBlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/md-blog.php', 'md-blog');

        $this->app->singleton(FrontMatterParser::class);

        $this->app->singleton(MarkdownParser::class, function ($app) {
            return new MarkdownParser(
                config('md-blog.commonmark', []),
            );
        });

        $this->app->singleton(PostRepository::class, function ($app) {
            $cacheStore = config('md-blog.cache.store');

            return new PostRepository(
                frontMatterParser: $app->make(FrontMatterParser::class),
                markdownParser: $app->make(MarkdownParser::class),
                cache: Cache::store($cacheStore),
                path: config('md-blog.path', 'resources/markdown/blog'),
                cacheEnabled: config('md-blog.cache.enabled', true),
                cacheTtl: config('md-blog.cache.ttl', 3600),
            );
        });

        $this->app->singleton(MdBlog::class, function ($app) {
            return new MdBlog(
                repository: $app->make(PostRepository::class),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/md-blog.php' => config_path('md-blog.php'),
            ], 'md-blog-config');
        }
    }
}
