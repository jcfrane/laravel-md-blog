<?php

return [
    'path' => env('MD_BLOG_PATH', 'resources/markdown/blog'),

    'route_prefix' => env('MD_BLOG_ROUTE_PREFIX', 'md-blog'),

    'cache' => [
        'enabled' => env('MD_BLOG_CACHE_ENABLED', true),
        'store' => env('MD_BLOG_CACHE_STORE', null),
        'ttl' => env('MD_BLOG_CACHE_TTL', 3600),
    ],

    'images' => [
        'enabled' => env('MD_BLOG_IMAGES_ENABLED', true),
        'cache_ttl' => env('MD_BLOG_IMAGES_CACHE_TTL', 86400),
    ],

    'commonmark' => [
        'html_input' => 'strip',
        'allow_unsafe_links' => false,
    ],
];
