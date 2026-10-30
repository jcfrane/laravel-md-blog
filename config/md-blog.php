<?php

return [
    'path' => env('MD_BLOG_PATH', 'resources/markdown/blog'),

    'route_prefix' => env('MD_BLOG_ROUTE_PREFIX', 'md-blog'),

    /*
     * Base URL prepended to image and cover URLs, e.g. https://huniapp.io.
     * Leave null for root-relative URLs (/md-blog/images/...). Set it when
     * posts are consumed outside the web page: JSON APIs, mobile apps, email.
     */
    'url' => env('MD_BLOG_URL', null),

    'post_class' => null,

    /*
     * Length in characters of the excerpt generated from the body when a post
     * has no `excerpt` front matter field.
     */
    'excerpt_length' => env('MD_BLOG_EXCERPT_LENGTH', 160),

    /*
     * Used to compute $post->readingTime (whole minutes).
     */
    'words_per_minute' => env('MD_BLOG_WORDS_PER_MINUTE', 200),

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
