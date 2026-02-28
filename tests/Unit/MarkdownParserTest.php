<?php

namespace JCFrane\MdBlog\Tests\Unit;

use JCFrane\MdBlog\Parsers\MarkdownParser;
use PHPUnit\Framework\TestCase;

class MarkdownParserTest extends TestCase
{
    private MarkdownParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MarkdownParser([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function test_converts_heading(): void
    {
        $html = $this->parser->toHtml('# Hello');

        $this->assertStringContainsString('<h1>Hello</h1>', $html);
    }

    public function test_converts_paragraph(): void
    {
        $html = $this->parser->toHtml('Some paragraph text.');

        $this->assertStringContainsString('<p>Some paragraph text.</p>', $html);
    }

    public function test_converts_bold_and_italic(): void
    {
        $html = $this->parser->toHtml('**bold** and *italic*');

        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<em>italic</em>', $html);
    }

    public function test_converts_unordered_list(): void
    {
        $md = "- One\n- Two\n- Three";
        $html = $this->parser->toHtml($md);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>One</li>', $html);
    }

    public function test_converts_gfm_table(): void
    {
        $md = "| A | B |\n|---|---|\n| 1 | 2 |";
        $html = $this->parser->toHtml($md);

        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('<td>1</td>', $html);
    }

    public function test_converts_code_block(): void
    {
        $md = "```php\necho 'hello';\n```";
        $html = $this->parser->toHtml($md);

        $this->assertStringContainsString('<code', $html);
        $this->assertStringContainsString("echo 'hello';", $html);
    }

    public function test_strips_html_input(): void
    {
        $md = 'Hello <script>alert("xss")</script> world';
        $html = $this->parser->toHtml($md);

        $this->assertStringNotContainsString('<script>', $html);
    }
}
