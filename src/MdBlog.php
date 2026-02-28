<?php

namespace JCFrane\MdBlog;

use Illuminate\Support\Collection;

class MdBlog
{
    public function __construct(
        private readonly PostRepository $repository,
    ) {}

    /**
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        return $this->repository->all()->filter(fn (Post $post) => $post->published);
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
     * @return Collection<int, Post>
     */
    public function whereTag(string $tag): Collection
    {
        return $this->all()->filter(fn (Post $post) => in_array($tag, $post->tags, true));
    }

    /**
     * @return Collection<int, Post>
     */
    public function whereCategory(string $category): Collection
    {
        return $this->all()->filter(fn (Post $post) => $post->category === $category);
    }

    /**
     * @return Collection<int, Post>
     */
    public function latest(): Collection
    {
        return $this->all()->sortByDesc(fn (Post $post) => $post->date->timestamp)->values();
    }

    public function clearCache(): void
    {
        $this->repository->clearCache();
    }
}
