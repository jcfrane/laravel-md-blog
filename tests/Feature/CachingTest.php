<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\Tests\TestCase;

class CachingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', true);
        $app['config']->set('md-blog.cache.store', null);
        $app['config']->set('md-blog.cache.ttl', 3600);
    }

    private function blog(): MdBlog
    {
        return $this->app->make(MdBlog::class);
    }

    public function test_posts_are_cached_after_first_load(): void
    {
        $posts = $this->blog()->all();
        $this->assertGreaterThan(0, $posts->count());

        // Second call should use cache (we verify it returns the same data)
        $postsAgain = $this->blog()->all();
        $this->assertSame($posts->count(), $postsAgain->count());
    }

    public function test_clear_cache_allows_fresh_load(): void
    {
        // Load posts to populate cache
        $this->blog()->all();

        // Clear the cache
        $this->blog()->clearCache();

        // Should still return the same data after cache clear
        $posts = $this->blog()->all();
        $this->assertGreaterThan(0, $posts->count());
    }

    public function test_cache_keys_are_md5_based(): void
    {
        $filePath = $this->fixturesPath('hello-world.md');
        $expectedKey = 'md_blog_post_' . md5($filePath);

        // Load to trigger caching
        $this->blog()->all();

        $this->assertTrue(Cache::has($expectedKey));
    }
}
