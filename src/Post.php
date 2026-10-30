<?php

namespace JCFrane\MdBlog;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

class Post implements Arrayable, Jsonable, JsonSerializable
{
    public function __construct(
        public readonly string $title,
        public readonly string $slug,
        public readonly Carbon $date,
        public readonly string $body,
        public readonly string $html,
        public readonly array $tags,
        public readonly string $category,
        public readonly string $excerpt,
        public readonly bool $published,
        public readonly array $meta,
        public readonly string $filePath,
        public readonly int $lastModified,
        public readonly ?string $cover = null,
        public readonly int $readingTime = 0,
    ) {}

    /**
     * @param  array{title: string, slug: string, date: Carbon, body: string, html: string, tags: array, category: string, excerpt: string, published: bool, meta: array, filePath: string, lastModified: int, cover?: string|null, readingTime?: int}  $attributes
     */
    public static function make(array $attributes): static
    {
        return new static(
            title: $attributes['title'],
            slug: $attributes['slug'],
            date: $attributes['date'],
            body: $attributes['body'],
            html: $attributes['html'],
            tags: $attributes['tags'],
            category: $attributes['category'],
            excerpt: $attributes['excerpt'],
            published: $attributes['published'],
            meta: $attributes['meta'],
            filePath: $attributes['filePath'],
            lastModified: $attributes['lastModified'],
            cover: $attributes['cover'] ?? null,
            readingTime: $attributes['readingTime'] ?? 0,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'date' => $this->date->toIso8601String(),
            'body' => $this->body,
            'html' => $this->html,
            'tags' => $this->tags,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'cover' => $this->cover,
            'readingTime' => $this->readingTime,
            'published' => $this->published,
            'meta' => $this->meta,
            'filePath' => $this->filePath,
            'lastModified' => $this->lastModified,
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
