<?php

namespace JCFrane\MdBlog\CommonMark;

use JCFrane\MdBlog\Support\ImageUrlResolver;
use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

class ImageUrlRewriteExtension implements ExtensionInterface
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

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new ImageUrlRewriteProcessor($this->resolver),
        );
    }
}
