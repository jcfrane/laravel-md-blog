<?php

namespace JCFrane\MdBlog\Tests\Feature;

use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\Tests\TestCase;

class MdBlogTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
    }

    private function blog(): MdBlog
    {
        return $this->app->make(MdBlog::class);
    }

    public function test_all_returns_only_published_posts(): void
    {
        $posts = $this->blog()->all();

        $this->assertGreaterThanOrEqual(2, $posts->count());

        $slugs = $posts->pluck('slug')->toArray();
        $this->assertContains('hello-world', $slugs);
        $this->assertContains('laravel-tips', $slugs);
        $this->assertNotContains('draft-post', $slugs);
    }

    public function test_find_returns_published_post_by_slug(): void
    {
        $post = $this->blog()->find('hello-world');

        $this->assertNotNull($post);
        $this->assertSame('Hello World', $post->title);
        $this->assertSame('hello-world', $post->slug);
    }

    public function test_find_returns_null_for_draft(): void
    {
        $post = $this->blog()->find('draft-post');

        $this->assertNull($post);
    }

    public function test_find_returns_null_for_nonexistent_slug(): void
    {
        $post = $this->blog()->find('does-not-exist');

        $this->assertNull($post);
    }

    public function test_where_tag_filters_correctly(): void
    {
        $posts = $this->blog()->whereTag('laravel');

        $this->assertTrue($posts->count() >= 1);
        $posts->each(function ($post) {
            $this->assertContains('laravel', $post->tags);
        });
    }

    public function test_where_tag_returns_empty_for_unknown_tag(): void
    {
        $posts = $this->blog()->whereTag('nonexistent-tag');

        $this->assertCount(0, $posts);
    }

    public function test_where_category_filters_correctly(): void
    {
        $posts = $this->blog()->whereCategory('tutorials');

        $this->assertTrue($posts->count() >= 1);
        $posts->each(function ($post) {
            $this->assertSame('tutorials', $post->category);
        });
    }

    public function test_where_category_returns_empty_for_unknown_category(): void
    {
        $posts = $this->blog()->whereCategory('nonexistent-category');

        $this->assertCount(0, $posts);
    }

    public function test_latest_returns_posts_in_date_descending_order(): void
    {
        $posts = $this->blog()->latest();

        $dates = $posts->pluck('date')->map->timestamp->toArray();

        for ($i = 0; $i < count($dates) - 1; $i++) {
            $this->assertGreaterThanOrEqual($dates[$i + 1], $dates[$i]);
        }
    }

    public function test_latest_excludes_drafts(): void
    {
        $posts = $this->blog()->latest();
        $slugs = $posts->pluck('slug')->toArray();

        $this->assertNotContains('draft-post', $slugs);
    }

    public function test_post_html_is_rendered(): void
    {
        $post = $this->blog()->find('hello-world');

        $this->assertNotNull($post);
        $this->assertStringContainsString('<h1>Hello World</h1>', $post->html);
        $this->assertStringContainsString('<strong>first post</strong>', $post->html);
    }

    public function test_post_meta_contains_custom_fields(): void
    {
        $post = $this->blog()->find('laravel-tips');

        $this->assertNotNull($post);
        $this->assertArrayHasKey('custom_field', $post->meta);
        $this->assertSame('custom_value', $post->meta['custom_field']);
    }

    public function test_no_frontmatter_file_has_sensible_defaults(): void
    {
        $post = $this->blog()->find('no-frontmatter');

        $this->assertNotNull($post);
        $this->assertSame('no-frontmatter', $post->slug);
        $this->assertSame('no-frontmatter', $post->title);
        $this->assertTrue($post->published);
        $this->assertSame([], $post->tags);
        $this->assertSame('', $post->category);
        $this->assertStringContainsString('<h1>No Front Matter</h1>', $post->html);
    }
}
