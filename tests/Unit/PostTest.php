<?php

namespace JCFrane\MdBlog\Tests\Unit;

use Carbon\Carbon;
use JCFrane\MdBlog\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase
{
    private function makePost(array $overrides = []): Post
    {
        return new Post(
            title: $overrides['title'] ?? 'Test Post',
            slug: $overrides['slug'] ?? 'test-post',
            date: $overrides['date'] ?? Carbon::parse('2026-01-01'),
            body: $overrides['body'] ?? '# Hello',
            html: $overrides['html'] ?? '<h1>Hello</h1>',
            tags: $overrides['tags'] ?? ['php', 'laravel'],
            category: $overrides['category'] ?? 'tutorials',
            excerpt: $overrides['excerpt'] ?? 'A test post.',
            published: $overrides['published'] ?? true,
            meta: $overrides['meta'] ?? ['custom' => 'value'],
            filePath: $overrides['filePath'] ?? '/tmp/test-post.md',
            lastModified: $overrides['lastModified'] ?? 1700000000,
        );
    }

    public function test_post_properties_are_accessible(): void
    {
        $post = $this->makePost();

        $this->assertSame('Test Post', $post->title);
        $this->assertSame('test-post', $post->slug);
        $this->assertSame('2026-01-01', $post->date->toDateString());
        $this->assertSame('# Hello', $post->body);
        $this->assertSame('<h1>Hello</h1>', $post->html);
        $this->assertSame(['php', 'laravel'], $post->tags);
        $this->assertSame('tutorials', $post->category);
        $this->assertSame('A test post.', $post->excerpt);
        $this->assertTrue($post->published);
        $this->assertSame(['custom' => 'value'], $post->meta);
        $this->assertSame('/tmp/test-post.md', $post->filePath);
        $this->assertSame(1700000000, $post->lastModified);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $post = $this->makePost();
        $array = $post->toArray();

        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('date', $array);
        $this->assertArrayHasKey('body', $array);
        $this->assertArrayHasKey('html', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('excerpt', $array);
        $this->assertArrayHasKey('published', $array);
        $this->assertArrayHasKey('meta', $array);
        $this->assertArrayHasKey('filePath', $array);
        $this->assertArrayHasKey('lastModified', $array);

        $this->assertSame('Test Post', $array['title']);
        $this->assertSame('test-post', $array['slug']);
    }

    public function test_to_json_returns_valid_json(): void
    {
        $post = $this->makePost();
        $json = $post->toJson();

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertSame('Test Post', $decoded['title']);
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $post = $this->makePost();

        $this->assertSame($post->toArray(), $post->jsonSerialize());
    }

    public function test_date_is_serialized_as_iso8601(): void
    {
        $post = $this->makePost();
        $array = $post->toArray();

        $this->assertStringContainsString('2026-01-01', $array['date']);
    }

    public function test_make_creates_post_from_array(): void
    {
        $attributes = [
            'title' => 'Test Post',
            'slug' => 'test-post',
            'date' => Carbon::parse('2026-01-01'),
            'body' => '# Hello',
            'html' => '<h1>Hello</h1>',
            'tags' => ['php', 'laravel'],
            'category' => 'tutorials',
            'excerpt' => 'A test post.',
            'published' => true,
            'meta' => ['custom' => 'value'],
            'filePath' => '/tmp/test-post.md',
            'lastModified' => 1700000000,
        ];

        $post = Post::make($attributes);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame('Test Post', $post->title);
        $this->assertSame('test-post', $post->slug);
        $this->assertSame('<h1>Hello</h1>', $post->html);
        $this->assertSame(['php', 'laravel'], $post->tags);
        $this->assertSame(1700000000, $post->lastModified);
    }

    public function test_make_returns_subclass_instance(): void
    {
        $attributes = [
            'title' => 'Test',
            'slug' => 'test',
            'date' => Carbon::parse('2026-01-01'),
            'body' => '# Hi',
            'html' => '<h1>Hi</h1>',
            'tags' => [],
            'category' => '',
            'excerpt' => '',
            'published' => true,
            'meta' => [],
            'filePath' => '/tmp/test.md',
            'lastModified' => 1700000000,
        ];

        $post = TestCustomPost::make($attributes);

        $this->assertInstanceOf(TestCustomPost::class, $post);
        $this->assertStringContainsString('<div class="custom">', $post->html);
    }
}

class TestCustomPost extends Post
{
    public static function make(array $attributes): static
    {
        $attributes['html'] = '<div class="custom">' . $attributes['html'] . '</div>';

        return parent::make($attributes);
    }
}
