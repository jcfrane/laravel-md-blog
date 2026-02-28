<?php

namespace JCFrane\MdBlog\Tests\Unit;

use JCFrane\MdBlog\Parsers\FrontMatterParser;
use PHPUnit\Framework\TestCase;

class FrontMatterParserTest extends TestCase
{
    private FrontMatterParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new FrontMatterParser();
    }

    public function test_parses_front_matter_and_body(): void
    {
        $content = <<<'MD'
---
title: My Post
tags: [php, laravel]
---

# Hello World
MD;

        $result = $this->parser->parse($content);

        $this->assertSame('My Post', $result['matter']['title']);
        $this->assertSame(['php', 'laravel'], $result['matter']['tags']);
        $this->assertStringContainsString('# Hello World', $result['body']);
    }

    public function test_returns_empty_matter_for_no_front_matter(): void
    {
        $content = '# Just Markdown';

        $result = $this->parser->parse($content);

        $this->assertSame([], $result['matter']);
        $this->assertStringContainsString('# Just Markdown', $result['body']);
    }

    public function test_handles_empty_front_matter(): void
    {
        $content = <<<'MD'
---
---

Some body text.
MD;

        $result = $this->parser->parse($content);

        $this->assertSame([], $result['matter']);
        $this->assertStringContainsString('Some body text.', $result['body']);
    }

    public function test_preserves_all_front_matter_fields(): void
    {
        $content = <<<'MD'
---
title: Test
slug: test
date: 2026-01-01
tags: [a, b]
category: cat
excerpt: ex
published: false
custom_key: custom_val
---

Body.
MD;

        $result = $this->parser->parse($content);

        $this->assertSame('Test', $result['matter']['title']);
        $this->assertSame('test', $result['matter']['slug']);
        $this->assertNotNull($result['matter']['date']);
        $this->assertSame(['a', 'b'], $result['matter']['tags']);
        $this->assertSame('cat', $result['matter']['category']);
        $this->assertSame('ex', $result['matter']['excerpt']);
        $this->assertFalse($result['matter']['published']);
        $this->assertSame('custom_val', $result['matter']['custom_key']);
    }
}
