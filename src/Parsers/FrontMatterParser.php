<?php

namespace JCFrane\MdBlog\Parsers;

use Spatie\YamlFrontMatter\YamlFrontMatter;

class FrontMatterParser
{
    /**
     * Parse a markdown string and return front matter + body.
     *
     * @return array{matter: array<string, mixed>, body: string}
     */
    public function parse(string $content): array
    {
        $document = YamlFrontMatter::parse($content);

        return [
            'matter' => $document->matter(),
            'body' => $document->body(),
        ];
    }
}
