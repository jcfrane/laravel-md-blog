<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\PostRepository;
use JCFrane\MdBlog\Tests\TestCase;

class SlugIndexTest extends TestCase
{
    private string $dir;

    protected function defineEnvironment($app): void
    {
        $this->dir = sys_get_temp_dir() . '/md-blog-index-' . uniqid();
        mkdir($this->dir);
        $this->dir = realpath($this->dir);

        foreach (['hello-world', 'laravel-tips', 'draft-post'] as $name) {
            copy($this->fixturesPath($name . '.md'), $this->dir . '/' . $name . '.md');
        }

        $app['config']->set('md-blog.path', $this->dir);
        $app['config']->set('md-blog.cache.enabled', true);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*.md') ?: []);
        rmdir($this->dir);

        parent::tearDown();
    }

    private function blog(): MdBlog
    {
        return $this->app->make(MdBlog::class);
    }

    private function indexKey(): string
    {
        return 'md_blog_index_v' . PostRepository::CACHE_VERSION . '_' . md5($this->dir);
    }

    public function test_index_is_written_after_listing(): void
    {
        $this->blog()->all();

        $index = Cache::get($this->indexKey());

        $this->assertIsArray($index);
        $this->assertSame($this->dir . '/hello-world.md', $index['hello-world']);
        $this->assertArrayHasKey('draft-post', $index);
    }

    public function test_find_uses_index_and_is_built_on_a_cold_cache(): void
    {
        $this->assertNull(Cache::get($this->indexKey()));

        $post = $this->blog()->find('laravel-tips');

        $this->assertNotNull($post);
        $this->assertIsArray(Cache::get($this->indexKey()));
    }

    public function test_find_recovers_when_index_points_to_a_moved_file(): void
    {
        $this->blog()->all();

        rename($this->dir . '/laravel-tips.md', $this->dir . '/renamed.md');

        $post = $this->blog()->find('laravel-tips');

        $this->assertNotNull($post);
        $this->assertSame($this->dir . '/renamed.md', $post->filePath);
        $this->assertSame($this->dir . '/renamed.md', Cache::get($this->indexKey())['laravel-tips']);
    }

    public function test_find_recovers_when_slug_changes_inside_the_file(): void
    {
        $this->blog()->all();

        $path = $this->dir . '/hello-world.md';
        file_put_contents($path, str_replace('slug: hello-world', 'slug: hello-again', file_get_contents($path)));
        touch($path, time() + 5);

        $this->assertNull($this->blog()->find('hello-world'));
        $this->assertSame('hello-again', $this->blog()->find('hello-again')->slug);
    }

    public function test_find_sees_a_new_file_not_yet_in_the_index(): void
    {
        $this->blog()->all();

        file_put_contents($this->dir . '/fresh.md', "---\ntitle: Fresh\n---\nBrand new.");

        $this->assertSame('Fresh', $this->blog()->find('fresh')->title);
    }

    public function test_find_returns_null_for_deleted_file(): void
    {
        $this->blog()->all();
        unlink($this->dir . '/laravel-tips.md');

        $this->assertNull($this->blog()->find('laravel-tips'));
    }

    public function test_clear_cache_forgets_the_index(): void
    {
        $this->blog()->all();
        $this->assertTrue(Cache::has($this->indexKey()));

        $this->blog()->clearCache();

        $this->assertFalse(Cache::has($this->indexKey()));
    }

    public function test_stale_cached_post_of_another_class_is_reparsed(): void
    {
        $this->blog()->all();

        $this->app['config']->set('md-blog.post_class', IndexedPost::class);
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        $this->assertInstanceOf(IndexedPost::class, $this->blog()->find('hello-world'));
    }
}

class IndexedPost extends \JCFrane\MdBlog\Post {}
