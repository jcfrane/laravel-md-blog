<?php

namespace JCFrane\MdBlog\Support;

/**
 * Turns image paths that are relative to the blog directory into URLs served by
 * the package's image route. Absolute, root-relative, protocol-relative and
 * data URLs are returned untouched.
 */
class ImageUrlResolver
{
    public function __construct(
        private readonly string $routePrefix,
        private readonly ?string $baseUrl = null,
    ) {}

    public function resolve(string $url): string
    {
        if (! $this->isRelative($url)) {
            return $url;
        }

        $base = $this->baseUrl !== null ? rtrim($this->baseUrl, '/') : '';

        return $base . '/' . trim($this->routePrefix, '/') . '/images/' . ltrim($url, './');
    }

    public function isRelative(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        // Absolute URLs (http://, https://, mailto:, etc.)
        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url)) {
            return false;
        }

        // Protocol-relative (//cdn.example.com/x.png)
        if (str_starts_with($url, '//')) {
            return false;
        }

        // Root-relative (/assets/x.png)
        if (str_starts_with($url, '/')) {
            return false;
        }

        if (str_starts_with($url, 'data:')) {
            return false;
        }

        return true;
    }
}
