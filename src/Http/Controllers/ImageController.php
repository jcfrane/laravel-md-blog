<?php

namespace JCFrane\MdBlog\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'ico'];

    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $this->validatePath($path);

        $blogPath = config('md-blog.path', 'resources/markdown/blog');
        $basePath = str_starts_with($blogPath, '/')
            ? $blogPath
            : base_path($blogPath);

        $filePath = $basePath . '/' . $path;
        $realBase = realpath($basePath);
        $realFile = realpath($filePath);

        if ($realBase === false || $realFile === false) {
            throw new NotFoundHttpException();
        }

        if (! str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException();
        }

        $cacheTtl = (int) config('md-blog.images.cache_ttl', 86400);

        return response()->file($realFile, [
            'Cache-Control' => 'public, max-age=' . $cacheTtl,
        ]);
    }

    private function validatePath(string $path): void
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            throw new NotFoundHttpException();
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new NotFoundHttpException();
        }
    }
}
