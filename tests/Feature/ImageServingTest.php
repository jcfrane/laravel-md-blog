<?php

namespace JCFrane\MdBlog\Tests\Feature;

use JCFrane\MdBlog\Tests\TestCase;

class ImageServingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
        $app['config']->set('md-blog.images.enabled', true);
        $app['config']->set('md-blog.images.cache_ttl', 86400);
        $app['config']->set('md-blog.route_prefix', 'md-blog');
    }

    public function test_serves_existing_image(): void
    {
        $response = $this->get('/md-blog/images/images/test.png');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_returns_cache_control_header(): void
    {
        $response = $this->get('/md-blog/images/images/test.png');

        $response->assertOk();
        $response->assertHeader('Cache-Control');
        $this->assertStringContainsString('max-age=86400', $response->headers->get('Cache-Control'));
    }

    public function test_returns_404_for_nonexistent_image(): void
    {
        $response = $this->get('/md-blog/images/images/nonexistent.png');

        $response->assertNotFound();
    }

    public function test_rejects_path_traversal(): void
    {
        $response = $this->get('/md-blog/images/../../../etc/passwd');

        $response->assertNotFound();
    }

    public function test_rejects_non_image_extension(): void
    {
        $response = $this->get('/md-blog/images/hello-world.md');

        $response->assertNotFound();
    }

    public function test_rejects_php_extension(): void
    {
        $response = $this->get('/md-blog/images/shell.php');

        $response->assertNotFound();
    }

    public function test_route_not_registered_when_images_disabled(): void
    {
        $this->app['config']->set('md-blog.images.enabled', false);

        $this->app->register(\JCFrane\MdBlog\MdBlogServiceProvider::class, true);

        $route = $this->app['router']->getRoutes()->getByName('md-blog.image');

        $this->assertNull($route);
    }

    public function test_post_with_image_has_rewritten_urls(): void
    {
        $repository = $this->app->make(\JCFrane\MdBlog\PostRepository::class);
        $post = $repository->findBySlug('post-with-image');

        $this->assertNotNull($post);
        $this->assertStringContainsString('src="/md-blog/images/images/test.png"', $post->html);
        $this->assertStringContainsString('src="https://example.com/photo.jpg"', $post->html);
        $this->assertStringContainsString('src="/assets/logo.png"', $post->html);
    }
}
