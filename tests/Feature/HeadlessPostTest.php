<?php

namespace JCFrane\MdBlog\Tests\Feature;

use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\PostRepository;
use JCFrane\MdBlog\Tests\TestCase;

class HeadlessPostTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
    }

    private function blog(): MdBlog
    {
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        return $this->app->make(MdBlog::class);
    }

    public function test_cover_is_resolved_to_the_image_route(): void
    {
        $post = $this->blog()->find('post-with-cover');

        $this->assertNotNull($post);
        $this->assertSame('/md-blog/images/images/test.png', $post->cover);
        $this->assertArrayNotHasKey('cover', $post->meta);
    }

    public function test_cover_and_images_become_absolute_when_url_is_configured(): void
    {
        $this->app['config']->set('md-blog.url', 'https://huniapp.io');
        $this->app->forgetInstance(\JCFrane\MdBlog\Support\ImageUrlResolver::class);
        $this->app->forgetInstance(\JCFrane\MdBlog\Parsers\MarkdownParser::class);

        $blog = $this->blog();

        $this->assertSame('https://huniapp.io/md-blog/images/images/test.png', $blog->find('post-with-cover')->cover);
        $this->assertStringContainsString(
            'src="https://huniapp.io/md-blog/images/images/test.png"',
            $blog->find('post-with-image')->html,
        );
    }

    public function test_absolute_cover_is_left_untouched(): void
    {
        $dir = sys_get_temp_dir() . '/md-blog-cover-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/remote.md', "---\ncover: https://cdn.test/c.jpg\n---\nBody.");
        $this->app['config']->set('md-blog.path', $dir);

        $post = $this->blog()->find('remote');

        $this->assertSame('https://cdn.test/c.jpg', $post->cover);

        unlink($dir . '/remote.md');
        rmdir($dir);
    }

    public function test_cover_is_null_when_not_declared(): void
    {
        $this->assertNull($this->blog()->find('hello-world')->cover);
    }

    public function test_cover_is_kept_as_written_when_images_are_disabled(): void
    {
        $this->app['config']->set('md-blog.images.enabled', false);
        $this->app->forgetInstance(\JCFrane\MdBlog\Parsers\MarkdownParser::class);

        $this->assertSame('images/test.png', $this->blog()->find('post-with-cover')->cover);
    }

    public function test_excerpt_is_generated_when_missing(): void
    {
        $post = $this->blog()->find('post-with-cover');

        $this->assertStringStartsWith('This post has a cover image', $post->excerpt);
        $this->assertStringNotContainsString('Post With Cover', $post->excerpt);
        $this->assertStringNotContainsString('echo', $post->excerpt);
        $this->assertLessThanOrEqual(163, mb_strlen($post->excerpt));
    }

    public function test_explicit_excerpt_wins(): void
    {
        $this->assertSame('Welcome to the blog!', $this->blog()->find('hello-world')->excerpt);
    }

    public function test_excerpt_length_is_configurable(): void
    {
        $this->app['config']->set('md-blog.excerpt_length', 40);

        $excerpt = $this->blog()->find('post-with-cover')->excerpt;

        $this->assertLessThanOrEqual(43, mb_strlen($excerpt));
        $this->assertStringEndsWith('...', $excerpt);
    }

    public function test_reading_time_is_computed(): void
    {
        $post = $this->blog()->find('laravel-tips');

        $this->assertSame(1, $post->readingTime);
    }

    public function test_reading_time_honours_words_per_minute(): void
    {
        $this->app['config']->set('md-blog.words_per_minute', 5);

        $this->assertGreaterThanOrEqual(5, $this->blog()->find('laravel-tips')->readingTime);
    }

    public function test_new_fields_are_serialized(): void
    {
        $array = $this->blog()->find('post-with-cover')->toArray();

        $this->assertSame('/md-blog/images/images/test.png', $array['cover']);
        $this->assertSame(1, $array['readingTime']);
    }
}
