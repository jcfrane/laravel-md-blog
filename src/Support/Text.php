<?php

namespace JCFrane\MdBlog\Support;

/**
 * Plain-text helpers used to derive excerpts and reading times from rendered HTML.
 */
class Text
{
    /**
     * Strip tags and collapse whitespace so the result reads as one paragraph.
     */
    public static function plain(string $html): string
    {
        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html) ?? $html;
        $text = preg_replace('#</(p|div|li|h[1-6]|blockquote|pre|tr|td|th|br)>#i', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Build an excerpt from rendered HTML. Headings and code blocks are dropped
     * first because they rarely read well as a teaser.
     */
    public static function excerpt(string $html, int $length = 160, string $end = '...'): string
    {
        $html = preg_replace('#<h[1-6]\b[^>]*>.*?</h[1-6]>#is', ' ', $html) ?? $html;
        $html = preg_replace('#<pre\b[^>]*>.*?</pre>#is', ' ', $html) ?? $html;

        $text = self::plain($html);

        if ($length <= 0 || mb_strlen($text) <= $length) {
            return $text;
        }

        $cut = mb_substr($text, 0, $length);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace > 0) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " \t\n\r\0\x0B.,;:!?") . $end;
    }

    public static function wordCount(string $html): int
    {
        $text = self::plain($html);

        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Estimated reading time in whole minutes. Non-empty text is at least one minute.
     */
    public static function readingTime(string $html, int $wordsPerMinute = 200): int
    {
        $words = self::wordCount($html);

        if ($words === 0) {
            return 0;
        }

        return max(1, (int) ceil($words / max(1, $wordsPerMinute)));
    }
}
