<?php

namespace JCFrane\MdBlog\Tests\Feature;

use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\PostRepository;
use JCFrane\MdBlog\Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
    }

    private function repository(): PostRepository
    {
        return $this->app->make(PostRepository::class);
    }

    public function test_all_returns_collection_of_posts(): void
    {
        $posts = $this->repository()->all();

        $this->assertGreaterThanOrEqual(4, $posts->count());
        $posts->each(fn ($post) => $this->assertInstanceOf(Post::class, $post));
    }

    public function test_all_includes_draft_posts(): void
    {
        $posts = $this->repository()->all();
        $slugs = $posts->pluck('slug')->toArray();

        $this->assertContains('draft-post', $slugs);
    }

    public function test_find_by_slug_returns_matching_post(): void
    {
        $post = $this->repository()->findBySlug('hello-world');

        $this->assertNotNull($post);
        $this->assertSame('Hello World', $post->title);
    }

    public function test_find_by_slug_returns_null_for_unknown(): void
    {
        $post = $this->repository()->findBySlug('nonexistent');

        $this->assertNull($post);
    }

    public function test_post_has_correct_properties(): void
    {
        $post = $this->repository()->findBySlug('hello-world');

        $this->assertNotNull($post);
        $this->assertSame('Hello World', $post->title);
        $this->assertSame('hello-world', $post->slug);
        $this->assertSame('2026-01-15', $post->date->toDateString());
        $this->assertSame(['general', 'intro'], $post->tags);
        $this->assertSame('announcements', $post->category);
        $this->assertSame('Welcome to the blog!', $post->excerpt);
        $this->assertTrue($post->published);
        $this->assertNotEmpty($post->body);
        $this->assertNotEmpty($post->html);
        $this->assertNotEmpty($post->filePath);
        $this->assertGreaterThan(0, $post->lastModified);
    }

    public function test_no_frontmatter_uses_filename_as_slug(): void
    {
        $post = $this->repository()->findBySlug('no-frontmatter');

        $this->assertNotNull($post);
        $this->assertSame('no-frontmatter', $post->slug);
    }

    public function test_returns_empty_collection_for_nonexistent_directory(): void
    {
        $this->app['config']->set('md-blog.path', '/tmp/nonexistent-md-blog-dir');

        $repo = $this->app->make(PostRepository::class);
        $posts = $repo->all();

        $this->assertCount(0, $posts);
    }
}
