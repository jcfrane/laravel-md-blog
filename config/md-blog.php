<?php

return [
    'path' => env('MD_BLOG_PATH', 'resources/markdown/blog'),

    'cache' => [
        'enabled' => env('MD_BLOG_CACHE_ENABLED', true),
        'store' => env('MD_BLOG_CACHE_STORE', null),
        'ttl' => env('MD_BLOG_CACHE_TTL', 3600),
    ],

    'commonmark' => [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ],
];
