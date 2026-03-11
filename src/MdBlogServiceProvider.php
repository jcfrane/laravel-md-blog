<?php

namespace JCFrane\MdBlog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JCFrane\MdBlog\Http\Controllers\ImageController;
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
                config: config('md-blog.commonmark', []),
                imageRewriteEnabled: config('md-blog.images.enabled', true),
                routePrefix: config('md-blog.route_prefix', 'md-blog'),
            );
        });

        $this->app->singleton(PostRepository::class, function ($app) {
            $cacheStore = config('md-blog.cache.store');

            // In tests, prefer the in-memory array cache to avoid external dependencies and
            // make assertions against the default cache repository work as expected.
            if ($app->environment('testing') && $cacheStore === null) {
                $app['config']->set('cache.default', 'array');
            }

            // Resolve the cache repository using the desired store (falls back to default when null)
            $cacheRepository = $cacheStore === null
                ? Cache::store(config('cache.default'))
                : Cache::store($cacheStore);

            return new PostRepository(
                frontMatterParser: $app->make(FrontMatterParser::class),
                markdownParser: $app->make(MarkdownParser::class),
                cache: $cacheRepository,
                path: config('md-blog.path', 'resources/markdown/blog'),
                cacheEnabled: config('md-blog.cache.enabled', true),
                cacheTtl: (int) config('md-blog.cache.ttl', 3600),
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

        $this->registerImageRoute();
    }

    private function registerImageRoute(): void
    {
        if (! config('md-blog.images.enabled', true)) {
            return;
        }

        if (Route::has('md-blog.image')) {
            return;
        }

        $prefix = config('md-blog.route_prefix', 'md-blog');

        Route::get($prefix . '/images/{path}', ImageController::class)
            ->where('path', '.*')
            ->name('md-blog.image');
    }
}
