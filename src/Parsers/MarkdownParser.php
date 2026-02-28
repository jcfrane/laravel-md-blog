<?php

namespace JCFrane\MdBlog\Parsers;

use JCFrane\MdBlog\CommonMark\ImageUrlRewriteExtension;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownParser
{
    private MarkdownConverter $converter;

    public function __construct(
        array $config = [],
        bool $imageRewriteEnabled = false,
        string $routePrefix = 'md-blog',
    ) {
        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        if ($imageRewriteEnabled) {
            $environment->addExtension(new ImageUrlRewriteExtension($routePrefix));
        }

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }
}
