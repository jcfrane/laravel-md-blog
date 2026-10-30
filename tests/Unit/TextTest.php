<?php

namespace JCFrane\MdBlog\Tests\Unit;

use JCFrane\MdBlog\Support\Text;
use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function test_plain_strips_tags_and_collapses_whitespace(): void
    {
        $html = "<h1>Title</h1>\n<p>Hello <strong>world</strong> &amp; friends.</p>\n<ul><li>One</li><li>Two</li></ul>";

        $this->assertSame('Title Hello world & friends. One Two', Text::plain($html));
    }

    public function test_excerpt_skips_headings_and_code_blocks(): void
    {
        $html = '<h1>Heading</h1><p>First paragraph.</p><pre><code>echo 1;</code></pre><p>Second paragraph.</p>';

        $this->assertSame('First paragraph. Second paragraph.', Text::excerpt($html, 160));
    }

    public function test_excerpt_truncates_on_word_boundary(): void
    {
        $html = '<p>The quick brown fox jumps over the lazy dog, again and again.</p>';

        $excerpt = Text::excerpt($html, 20);

        $this->assertSame('The quick brown fox...', $excerpt);
    }

    public function test_excerpt_returns_full_text_when_short(): void
    {
        $this->assertSame('Short.', Text::excerpt('<p>Short.</p>', 160));
    }

    public function test_excerpt_handles_multibyte_text(): void
    {
        $html = '<p>Magandang gabi sa inyong lahat, matulog nang mahimbing ngayong gabi.</p>';

        $excerpt = Text::excerpt($html, 30);

        $this->assertStringEndsWith('...', $excerpt);
        $this->assertLessThanOrEqual(33, mb_strlen($excerpt));
    }

    public function test_word_count(): void
    {
        $this->assertSame(0, Text::wordCount(''));
        $this->assertSame(5, Text::wordCount('<p>one two</p><p>three <em>four</em> five</p>'));
    }

    public function test_reading_time_is_at_least_one_minute_for_text(): void
    {
        $this->assertSame(0, Text::readingTime(''));
        $this->assertSame(1, Text::readingTime('<p>just a few words</p>'));
    }

    public function test_reading_time_rounds_up(): void
    {
        $html = '<p>' . str_repeat('word ', 450) . '</p>';

        $this->assertSame(3, Text::readingTime($html, 200));
        $this->assertSame(5, Text::readingTime($html, 100));
    }
}
