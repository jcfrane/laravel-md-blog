<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Carbon\Carbon;
use InvalidArgumentException;
use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\PostRepository;
use JCFrane\MdBlog\Tests\TestCase;

class CustomPostTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
    }

    public function test_default_config_uses_base_post_class(): void
    {
        $blog = $this->app->make(MdBlog::class);
        $post = $blog->find('hello-world');

        $this->assertNotNull($post);
        $this->assertSame(Post::class, get_class($post));
    }

    public function test_custom_post_class_is_used_when_configured(): void
    {
        $this->app['config']->set('md-blog.post_class', HighlightedPost::class);
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        $blog = $this->app->make(MdBlog::class);
        $post = $blog->find('hello-world');

        $this->assertNotNull($post);
        $this->assertInstanceOf(HighlightedPost::class, $post);
    }

    public function test_custom_post_class_transformations_are_applied(): void
    {
        $this->app['config']->set('md-blog.post_class', HighlightedPost::class);
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        $blog = $this->app->make(MdBlog::class);
        $post = $blog->find('hello-world');

        $this->assertNotNull($post);
        $this->assertStringStartsWith('<div class="highlighted">', $post->html);
        $this->assertStringEndsWith('</div>', $post->html);
    }

    public function test_invalid_post_class_throws_exception(): void
    {
        $this->app['config']->set('md-blog.post_class', 'App\\NonExistent\\PostClass');
        $this->app->forgetInstance(PostRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        $this->app->make(PostRepository::class);
    }

    public function test_post_class_not_extending_post_throws_exception(): void
    {
        $this->app['config']->set('md-blog.post_class', NotAPost::class);
        $this->app->forgetInstance(PostRepository::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        $this->app->make(PostRepository::class);
    }

    public function test_custom_post_class_works_with_caching(): void
    {
        $this->app['config']->set('md-blog.post_class', HighlightedPost::class);
        $this->app['config']->set('md-blog.cache.enabled', true);
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        $blog = $this->app->make(MdBlog::class);

        // First load (populates cache)
        $post = $blog->find('hello-world');
        $this->assertInstanceOf(HighlightedPost::class, $post);
        $this->assertStringStartsWith('<div class="highlighted">', $post->html);

        // Second load (from cache)
        $postAgain = $blog->find('hello-world');
        $this->assertInstanceOf(HighlightedPost::class, $postAgain);
        $this->assertStringStartsWith('<div class="highlighted">', $postAgain->html);
    }

    public function test_custom_post_class_works_with_all_query_methods(): void
    {
        $this->app['config']->set('md-blog.post_class', HighlightedPost::class);
        $this->app->forgetInstance(PostRepository::class);
        $this->app->forgetInstance(MdBlog::class);

        $blog = $this->app->make(MdBlog::class);

        $blog->all()->each(function ($post) {
            $this->assertInstanceOf(HighlightedPost::class, $post);
        });

        $blog->latest()->each(function ($post) {
            $this->assertInstanceOf(HighlightedPost::class, $post);
        });

        $blog->whereTag('laravel')->each(function ($post) {
            $this->assertInstanceOf(HighlightedPost::class, $post);
        });
    }
}

class HighlightedPost extends Post
{
    public static function make(array $attributes): static
    {
        $attributes['html'] = '<div class="highlighted">' . $attributes['html'] . '</div>';

        return parent::make($attributes);
    }
}

class NotAPost
{
    // Does not extend Post
}
