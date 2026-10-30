<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use JCFrane\MdBlog\MdBlog;
use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\Tests\TestCase;

class PostQueryTest extends TestCase
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

    public function test_get_returns_published_posts_newest_first(): void
    {
        $posts = $this->blog()->query()->get();

        $this->assertNotContains('draft-post', $posts->pluck('slug'));
        $this->assertSame($posts->max(fn (Post $p) => $p->date->timestamp), $posts->first()->date->timestamp);

        $slugs = $posts->pluck('slug')->all();
        $this->assertLessThan(array_search('laravel-tips', $slugs), array_search('post-with-cover', $slugs));

        $timestamps = $posts->map(fn (Post $p) => $p->date->timestamp)->all();
        $sorted = $timestamps;
        rsort($sorted);
        $this->assertSame($sorted, $timestamps);
    }

    public function test_all_is_newest_first(): void
    {
        $posts = $this->blog()->all();

        $this->assertSame($posts->max(fn (Post $p) => $p->date->timestamp), $posts->first()->date->timestamp);
    }

    public function test_oldest_reverses_order(): void
    {
        $posts = $this->blog()->query()->oldest()->get();

        // no-frontmatter has no date and falls back to mtime, so ignore it here.
        $dated = $posts->reject(fn (Post $p) => $p->slug === 'no-frontmatter')->values();

        $this->assertSame('hello-world', $dated->first()->slug);
    }

    public function test_where_tag_accepts_many_tags(): void
    {
        $slugs = $this->blog()->query()->whereTag(['intro', 'images'])->get()->pluck('slug')->all();

        sort($slugs);
        $this->assertSame(['hello-world', 'post-with-cover'], $slugs);
    }

    public function test_where_all_tags(): void
    {
        $slugs = $this->blog()->query()->whereAllTags(['laravel', 'php'])->get()->pluck('slug')->all();

        $this->assertSame(['laravel-tips'], $slugs);
    }

    public function test_chained_filters_narrow_results(): void
    {
        $slugs = $this->blog()->query()
            ->whereCategory('tutorials')
            ->whereTag('images')
            ->get()
            ->pluck('slug')
            ->all();

        $this->assertSame(['post-with-cover'], $slugs);
    }

    public function test_where_meta_by_key_and_value(): void
    {
        $query = $this->blog()->query();

        $this->assertSame(['laravel-tips'], $query->whereMeta('custom_field')->get()->pluck('slug')->all());
        $this->assertSame(['laravel-tips'], $this->blog()->query()->whereMeta('custom_field', 'custom_value')->get()->pluck('slug')->all());
        $this->assertCount(0, $this->blog()->query()->whereMeta('custom_field', 'other')->get());
    }

    public function test_where_year(): void
    {
        $this->assertGreaterThanOrEqual(4, $this->blog()->query()->whereYear(2026)->count());
        $this->assertSame(0, $this->blog()->query()->whereYear(1999)->count());
    }

    public function test_search_is_case_insensitive_over_title_and_body(): void
    {
        $this->assertSame(['laravel-tips'], $this->blog()->query()->search('SERVICE PROVIDERS')->get()->pluck('slug')->all());
        $this->assertSame(['hello-world'], $this->blog()->query()->search('welcome')->get()->pluck('slug')->all());
        $this->assertCount(0, $this->blog()->query()->search('zzz-not-there')->get());
    }

    public function test_order_by_property(): void
    {
        $titles = $this->blog()->query()->orderBy('title')->get()->pluck('title')->all();

        $sorted = $titles;
        sort($sorted);
        $this->assertSame($sorted, $titles);
    }

    public function test_take_and_skip(): void
    {
        $all = $this->blog()->query()->get();
        $page = $this->blog()->query()->skip(1)->take(2)->get();

        $this->assertCount(2, $page);
        $this->assertSame($all[1]->slug, $page[0]->slug);
        $this->assertSame($all[2]->slug, $page[1]->slug);
    }

    public function test_first_count_and_exists(): void
    {
        $this->assertInstanceOf(Post::class, $this->blog()->query()->first());
        $this->assertSame(5, $this->blog()->query()->count());
        $this->assertTrue($this->blog()->query()->whereTag('laravel')->exists());
        $this->assertFalse($this->blog()->query()->whereTag('nope')->exists());
        $this->assertNull($this->blog()->query()->whereTag('nope')->first());
    }

    public function test_paginate_returns_length_aware_paginator(): void
    {
        $paginator = $this->blog()->paginate(2);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(5, $paginator->total());
        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(1, $paginator->currentPage());
        $this->assertSame(3, $paginator->lastPage());
        $this->assertCount(2, $paginator->items());
        $this->assertSame($this->blog()->query()->first()->slug, $paginator->items()[0]->slug);
    }

    public function test_paginate_explicit_page(): void
    {
        $paginator = $this->blog()->query()->paginate(2, 'page', 3);

        $this->assertSame(3, $paginator->currentPage());
        $this->assertCount(1, $paginator->items());
        $this->assertFalse($paginator->hasMorePages());
    }

    public function test_paginate_reads_page_from_request(): void
    {
        Paginator::currentPageResolver(fn () => 2);

        $paginator = $this->blog()->query()->whereCategory(['tutorials', 'announcements'])->paginate(1);

        $this->assertSame(2, $paginator->currentPage());
        $this->assertSame(3, $paginator->total());
        $this->assertCount(1, $paginator->items());
    }

    public function test_paginate_with_filters_serializes_for_inertia(): void
    {
        $array = $this->blog()->query()->whereTag('laravel')->paginate(10)->toArray();

        $this->assertSame(2, $array['total']);
        $this->assertArrayHasKey('links', $array);
        $this->assertSame('laravel-tips', $array['data'][1]['slug']);
        $this->assertArrayHasKey('readingTime', $array['data'][0]);
    }

    public function test_previous_and_next_follow_publication_order(): void
    {
        $blog = $this->blog();
        $middle = $blog->find('laravel-tips'); // 2026-02-10

        $this->assertSame('post-with-image', $blog->previous($middle)->slug); // 2026-02-01
        $this->assertSame('post-with-cover', $blog->next($middle)->slug); // 2026-02-20
    }

    public function test_previous_is_null_for_oldest_and_next_null_for_newest(): void
    {
        $blog = $this->blog();

        $oldest = $blog->query()->oldest()->first();
        $newest = $blog->query()->latest()->first();

        $this->assertNull($blog->previous($oldest));
        $this->assertNull($blog->next($newest));
    }

    public function test_previous_and_next_skip_drafts(): void
    {
        $blog = $this->blog();
        $post = $blog->find('post-with-cover'); // draft-post (2026-03-01) is the next by date but unpublished

        $next = $blog->next($post);

        $this->assertNotSame('draft-post', $next?->slug);
        $this->assertTrue($next === null || $next->published);
    }

    public function test_related_ranks_shared_tags_above_shared_category(): void
    {
        $blog = $this->blog();
        $post = $blog->find('post-with-cover'); // tags laravel, images; category tutorials

        $related = $blog->related($post);

        $this->assertNotContains('post-with-cover', $related->pluck('slug'));
        $this->assertSame('laravel-tips', $related->first()->slug); // shares tag + category
        $this->assertCount(1, $related);
    }

    public function test_related_respects_limit_and_returns_empty_without_overlap(): void
    {
        $blog = $this->blog();

        $this->assertCount(0, $blog->related($blog->find('no-frontmatter')));
        $this->assertCount(0, $blog->related($blog->find('post-with-cover'), 0));
    }
}
