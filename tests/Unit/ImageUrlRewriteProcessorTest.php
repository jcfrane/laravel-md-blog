<?php

namespace JCFrane\MdBlog\Tests\Unit;

use JCFrane\MdBlog\Parsers\MarkdownParser;
use PHPUnit\Framework\TestCase;

class ImageUrlRewriteProcessorTest extends TestCase
{
    private MarkdownParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MarkdownParser(
            config: [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ],
            imageRewriteEnabled: true,
            routePrefix: 'md-blog',
        );
    }

    public function test_rewrites_relative_image_url(): void
    {
        $html = $this->parser->toHtml('![photo](images/photo.png)');

        $this->assertStringContainsString('src="/md-blog/images/images/photo.png"', $html);
    }

    public function test_rewrites_nested_relative_image_url(): void
    {
        $html = $this->parser->toHtml('![photo](images/sub/photo.png)');

        $this->assertStringContainsString('src="/md-blog/images/images/sub/photo.png"', $html);
    }

    public function test_does_not_rewrite_absolute_url(): void
    {
        $html = $this->parser->toHtml('![photo](https://example.com/photo.png)');

        $this->assertStringContainsString('src="https://example.com/photo.png"', $html);
    }

    public function test_does_not_rewrite_protocol_relative_url(): void
    {
        $html = $this->parser->toHtml('![photo](//cdn.example.com/photo.png)');

        $this->assertStringContainsString('src="//cdn.example.com/photo.png"', $html);
    }

    public function test_does_not_rewrite_root_relative_url(): void
    {
        $html = $this->parser->toHtml('![photo](/assets/photo.png)');

        $this->assertStringContainsString('src="/assets/photo.png"', $html);
    }

    public function test_does_not_rewrite_data_uri(): void
    {
        $html = $this->parser->toHtml('![pixel](data:image/png;base64,abc)');

        $this->assertStringContainsString('src="data:image/png;base64,abc"', $html);
    }

    public function test_uses_custom_route_prefix(): void
    {
        $parser = new MarkdownParser(
            config: [],
            imageRewriteEnabled: true,
            routePrefix: 'blog',
        );

        $html = $parser->toHtml('![photo](images/photo.png)');

        $this->assertStringContainsString('src="/blog/images/images/photo.png"', $html);
    }

    public function test_does_not_rewrite_when_disabled(): void
    {
        $parser = new MarkdownParser(
            config: [],
            imageRewriteEnabled: false,
        );

        $html = $parser->toHtml('![photo](images/photo.png)');

        $this->assertStringContainsString('src="images/photo.png"', $html);
    }

    public function test_rewrites_multiple_images(): void
    {
        $md = "![a](images/a.png)\n\n![b](images/b.jpg)";
        $html = $this->parser->toHtml($md);

        $this->assertStringContainsString('src="/md-blog/images/images/a.png"', $html);
        $this->assertStringContainsString('src="/md-blog/images/images/b.jpg"', $html);
    }

    public function test_leaves_links_untouched(): void
    {
        $html = $this->parser->toHtml('[click here](pages/about.html)');

        $this->assertStringContainsString('href="pages/about.html"', $html);
        $this->assertStringNotContainsString('md-blog', $html);
    }
}
