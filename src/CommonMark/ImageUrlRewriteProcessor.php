<?php

namespace JCFrane\MdBlog\CommonMark;

use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Query;

class ImageUrlRewriteProcessor
{
    public function __construct(
        private readonly string $routePrefix,
    ) {}

    public function __invoke(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();

        $images = (new Query())
            ->where(Query::type(Image::class))
            ->findAll($document);

        foreach ($images as $image) {
            /** @var Image $image */
            $url = $image->getUrl();

            if ($this->isRelativeUrl($url)) {
                $image->setUrl('/' . trim($this->routePrefix, '/') . '/images/' . $url);
            }
        }
    }

    private function isRelativeUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        // Skip absolute URLs (http://, https://, etc.)
        if (preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url)) {
            return false;
        }

        // Skip protocol-relative URLs (//example.com)
        if (str_starts_with($url, '//')) {
            return false;
        }

        // Skip root-relative URLs (/path/to/image)
        if (str_starts_with($url, '/')) {
            return false;
        }

        // Skip data URIs
        if (str_starts_with($url, 'data:')) {
            return false;
        }

        return true;
    }
}
