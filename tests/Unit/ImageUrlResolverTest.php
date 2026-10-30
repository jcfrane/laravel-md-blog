<?php

namespace JCFrane\MdBlog\Tests\Unit;

use JCFrane\MdBlog\Parsers\MarkdownParser;
use JCFrane\MdBlog\Support\ImageUrlResolver;
use PHPUnit\Framework\TestCase;

class ImageUrlResolverTest extends TestCase
{
    public function test_resolves_root_relative_without_base_url(): void
    {
        $resolver = new ImageUrlResolver('md-blog');

        $this->assertSame('/md-blog/images/images/photo.png', $resolver->resolve('images/photo.png'));
    }

    public function test_resolves_absolute_with_base_url(): void
    {
        $resolver = new ImageUrlResolver('blog', 'https://huniapp.io/');

        $this->assertSame('https://huniapp.io/blog/images/images/photo.png', $resolver->resolve('images/photo.png'));
    }

    public function test_strips_leading_dot_slash(): void
    {
        $resolver = new ImageUrlResolver('md-blog', 'https://example.com');

        $this->assertSame('https://example.com/md-blog/images/images/photo.png', $resolver->resolve('./images/photo.png'));
    }

    public function test_leaves_non_relative_urls_alone(): void
    {
        $resolver = new ImageUrlResolver('md-blog', 'https://example.com');

        foreach (['https://cdn.test/a.png', '//cdn.test/a.png', '/assets/a.png', 'data:image/png;base64,abc', ''] as $url) {
            $this->assertSame($url, $resolver->resolve($url));
        }
    }

    public function test_markdown_parser_rewrites_to_absolute_url_when_base_url_given(): void
    {
        $parser = new MarkdownParser(
            config: [],
            imageRewriteEnabled: true,
            routePrefix: 'md-blog',
            baseUrl: 'https://huniapp.io',
        );

        $html = $parser->toHtml('![photo](images/photo.png)');

        $this->assertStringContainsString('src="https://huniapp.io/md-blog/images/images/photo.png"', $html);
    }
}
