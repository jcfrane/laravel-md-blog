<?php

return [
    'path' => env('MD_BLOG_PATH', 'resources/markdown/blog'),

    'route_prefix' => env('MD_BLOG_ROUTE_PREFIX', 'md-blog'),

    'post_class' => null,

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

    'mail' => [
        'enabled' => env('MD_BLOG_MAIL_ENABLED', false),
        'recipient_model' => env('MD_BLOG_MAIL_RECIPIENT_MODEL', null),
        'queue' => env('MD_BLOG_MAIL_QUEUE', false),
        'queue_connection' => env('MD_BLOG_MAIL_QUEUE_CONNECTION', null),
        'queue_name' => env('MD_BLOG_MAIL_QUEUE_NAME', null),
        'chunk_size' => env('MD_BLOG_MAIL_CHUNK_SIZE', 50),
        'middleware' => ['auth'],
        'subject_prefix' => env('MD_BLOG_MAIL_SUBJECT_PREFIX', ''),
    ],
];
