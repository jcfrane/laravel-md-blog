<?php

namespace JCFrane\MdBlog\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;

class ImageUrlRewriteExtension implements ExtensionInterface
{
    public function __construct(
        private readonly string $routePrefix,
    ) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(
            DocumentParsedEvent::class,
            new ImageUrlRewriteProcessor($this->routePrefix),
        );
    }
}
