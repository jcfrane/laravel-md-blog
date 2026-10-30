<?php

namespace JCFrane\MdBlog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use JCFrane\MdBlog\Services\PostMailService;

class MdBlog
{
    public function __construct(
        private readonly PostRepository $repository,
        private readonly PostMailService $postMailService,
    ) {}

    /**
     * Start a fluent query over published posts.
     */
    public function query(): PostQuery
    {
        return new PostQuery($this->repository);
    }

    /**
     * All published posts, newest first.
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function find(string $slug): ?Post
    {
        $post = $this->repository->findBySlug($slug);

        if ($post === null || ! $post->published) {
            return null;
        }

        return $post;
    }

    /**
     * @param  string|array<int, string>  $tag
     * @return Collection<int, Post>
     */
    public function whereTag(string|array $tag): Collection
    {
        return $this->query()->whereTag($tag)->get();
    }

    /**
     * @param  string|array<int, string>  $category
     * @return Collection<int, Post>
     */
    public function whereCategory(string|array $category): Collection
    {
        return $this->query()->whereCategory($category)->get();
    }

    /**
     * @return Collection<int, Post>
     */
    public function latest(): Collection
    {
        return $this->query()->latest()->get();
    }

    /**
     * @return LengthAwarePaginator<int, Post>
     */
    public function paginate(int $perPage = 10, string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage, $pageName, $page);
    }

    /**
     * The post published just before the given one (older), if any.
     */
    public function previous(Post $post): ?Post
    {
        return $this->query()
            ->latest()
            ->where(fn (Post $other) => $this->compare($other, $post) < 0)
            ->first();
    }

    /**
     * The post published just after the given one (newer), if any.
     */
    public function next(Post $post): ?Post
    {
        return $this->query()
            ->oldest()
            ->where(fn (Post $other) => $this->compare($other, $post) > 0)
            ->first();
    }

    /**
     * Posts sharing tags or a category with the given one, best match first.
     *
     * @return Collection<int, Post>
     */
    public function related(Post $post, int $limit = 3): Collection
    {
        return $this->query()
            ->where(fn (Post $other) => $other->slug !== $post->slug)
            ->get()
            ->map(fn (Post $other) => ['post' => $other, 'score' => $this->relatedScore($post, $other)])
            ->filter(fn (array $entry) => $entry['score'] > 0)
            ->sortBy([
                fn (array $a, array $b) => $b['score'] <=> $a['score'],
                fn (array $a, array $b) => $b['post']->date->timestamp <=> $a['post']->date->timestamp,
            ])
            ->take($limit)
            ->map(fn (array $entry) => $entry['post'])
            ->values();
    }

    public function clearCache(): void
    {
        $this->repository->clearCache();
    }

    public function sendPost(string $path): int
    {
        return $this->postMailService->send($path);
    }

    /**
     * Chronological order with the slug as a tie-breaker so prev/next never
     * skip or repeat posts that share a date.
     */
    private function compare(Post $a, Post $b): int
    {
        return [$a->date->timestamp, $a->slug] <=> [$b->date->timestamp, $b->slug];
    }

    private function relatedScore(Post $post, Post $other): int
    {
        $sharedTags = count(array_intersect($post->tags, $other->tags));
        $sameCategory = $post->category !== '' && $post->category === $other->category ? 1 : 0;

        return $sharedTags * 2 + $sameCategory;
    }
}
