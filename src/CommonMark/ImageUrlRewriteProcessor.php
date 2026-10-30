<?php

namespace JCFrane\MdBlog\CommonMark;

use JCFrane\MdBlog\Support\ImageUrlResolver;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Node\Query;

class ImageUrlRewriteProcessor
{
    private readonly ImageUrlResolver $resolver;

    public function __construct(
        string|ImageUrlResolver $routePrefix,
        ?string $baseUrl = null,
    ) {
        $this->resolver = $routePrefix instanceof ImageUrlResolver
            ? $routePrefix
            : new ImageUrlResolver($routePrefix, $baseUrl);
    }

    public function __invoke(DocumentParsedEvent $event): void
    {
        $document = $event->getDocument();

        $images = (new Query())
            ->where(Query::type(Image::class))
            ->findAll($document);

        foreach ($images as $image) {
            /** @var Image $image */
            $image->setUrl($this->resolver->resolve($image->getUrl()));
        }
    }
}
