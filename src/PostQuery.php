<?php

namespace JCFrane\MdBlog;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Fluent, chainable query over the markdown posts. Only published posts are
 * returned. Results are newest first unless you say otherwise.
 *
 *     MdBlog::query()->whereTag('sleep')->latest()->paginate(9);
 */
class PostQuery
{
    /** @var list<Closure(Post): bool> */
    private array $filters = [];

    /** @var Closure(Post): mixed */
    private Closure $sortBy;

    private bool $descending = true;

    private ?int $limit = null;

    private int $offset = 0;

    public function __construct(
        private readonly PostRepository $repository,
    ) {
        $this->sortBy = fn (Post $post) => $post->date->timestamp;
    }

    /**
     * Keep posts carrying any of the given tags.
     *
     * @param  string|array<int, string>  $tags
     */
    public function whereTag(string|array $tags): static
    {
        $tags = (array) $tags;

        return $this->where(fn (Post $post) => count(array_intersect($tags, $post->tags)) > 0);
    }

    /**
     * Keep posts carrying every one of the given tags.
     *
     * @param  array<int, string>  $tags
     */
    public function whereAllTags(array $tags): static
    {
        return $this->where(fn (Post $post) => count(array_diff($tags, $post->tags)) === 0);
    }

    /**
     * @param  string|array<int, string>  $categories
     */
    public function whereCategory(string|array $categories): static
    {
        $categories = (array) $categories;

        return $this->where(fn (Post $post) => in_array($post->category, $categories, true));
    }

    /**
     * Filter on an extra front matter field. With one argument, keeps posts
     * that define the key; with two, keeps posts whose value matches.
     */
    public function whereMeta(string $key, mixed $value = null): static
    {
        if (func_num_args() === 1) {
            return $this->where(fn (Post $post) => array_key_exists($key, $post->meta));
        }

        return $this->where(fn (Post $post) => array_key_exists($key, $post->meta) && $post->meta[$key] == $value);
    }

    public function whereYear(int $year): static
    {
        return $this->where(fn (Post $post) => $post->date->year === $year);
    }

    /**
     * Case-insensitive match against title, excerpt, tags and body.
     */
    public function search(string $term): static
    {
        $term = trim($term);

        if ($term === '') {
            return $this;
        }

        return $this->where(function (Post $post) use ($term) {
            $haystack = implode(' ', [$post->title, $post->excerpt, implode(' ', $post->tags), $post->body]);

            return Str::contains($haystack, $term, ignoreCase: true);
        });
    }

    /**
     * @param  Closure(Post): bool  $callback
     */
    public function where(Closure $callback): static
    {
        $this->filters[] = $callback;

        return $this;
    }

    public function latest(): static
    {
        return $this->orderBy('date', 'desc');
    }

    public function oldest(): static
    {
        return $this->orderBy('date', 'asc');
    }

    /**
     * Sort by a Post property name or a callback returning a sortable value.
     *
     * @param  string|Closure(Post): mixed  $key
     */
    public function orderBy(string|Closure $key, string $direction = 'asc'): static
    {
        $this->sortBy = $key instanceof Closure
            ? $key
            : fn (Post $post) => $key === 'date' ? $post->date->timestamp : $post->{$key};
        $this->descending = strtolower($direction) === 'desc';

        return $this;
    }

    public function take(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function limit(int $limit): static
    {
        return $this->take($limit);
    }

    public function skip(int $offset): static
    {
        $this->offset = max(0, $offset);

        return $this;
    }

    public function offset(int $offset): static
    {
        return $this->skip($offset);
    }

    /**
     * @return Collection<int, Post>
     */
    public function get(): Collection
    {
        $posts = $this->matching();

        if ($this->offset > 0) {
            $posts = $posts->slice($this->offset);
        }

        if ($this->limit !== null) {
            $posts = $posts->take($this->limit);
        }

        return $posts->values();
    }

    public function first(): ?Post
    {
        return $this->get()->first();
    }

    public function count(): int
    {
        return $this->matching()->count();
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Paginate like Eloquent does: the current page is read from the request
     * and the result renders straight into Blade or Inertia.
     *
     * @return LengthAwarePaginatorContract<int, Post>
     */
    public function paginate(int $perPage = 10, string $pageName = 'page', ?int $page = null): LengthAwarePaginatorContract
    {
        $page = $page ?? Paginator::resolveCurrentPage($pageName);
        $perPage = max(1, $perPage);

        $posts = $this->matching();

        return new LengthAwarePaginator(
            items: $posts->forPage($page, $perPage)->values(),
            total: $posts->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }

    /**
     * Filtered and sorted, before offset and limit.
     *
     * @return Collection<int, Post>
     */
    private function matching(): Collection
    {
        $posts = $this->repository->all()->filter(fn (Post $post) => $post->published);

        foreach ($this->filters as $filter) {
            $posts = $posts->filter($filter);
        }

        $sorted = $this->descending
            ? $posts->sortByDesc($this->sortBy)
            : $posts->sortBy($this->sortBy);

        return $sorted->values();
    }
}
