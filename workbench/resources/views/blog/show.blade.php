<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $post->title }}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; line-height: 1.6; }
        a.back { color: #2563eb; text-decoration: none; font-size: 0.875rem; }
        a.back:hover { text-decoration: underline; }
        .meta { color: #6b7280; font-size: 0.875rem; margin-bottom: 2rem; }
        .tags span { background: #f3f4f6; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-right: 0.25rem; }
        .content { margin-top: 1rem; }
        .content h1, .content h2, .content h3 { margin-top: 1.5rem; }
        .content pre { background: #f3f4f6; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; }
        .content code { font-size: 0.875em; }
        .content table { border-collapse: collapse; width: 100%; }
        .content th, .content td { border: 1px solid #e5e7eb; padding: 0.5rem; text-align: left; }
        .content th { background: #f9fafb; }
        .cover { width: 100%; max-height: 320px; object-fit: cover; border-radius: 0.5rem; }
        nav.pager { display: flex; justify-content: space-between; gap: 1rem; margin-top: 3rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; }
        nav.pager a { color: #2563eb; text-decoration: none; }
        .related { margin-top: 2rem; }
        .related h3 { font-size: 1rem; color: #6b7280; }
        .related a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <a class="back" href="/blog">&larr; Back to blog</a>

    @if($post->cover)
        <img class="cover" src="{{ $post->cover }}" alt="">
    @endif

    <h1>{{ $post->title }}</h1>

    <div class="meta">
        {{ $post->date->format('F j, Y') }}
        &middot; {{ $post->readingTime }} min read
        @if($post->category)
            &middot; {{ $post->category }}
        @endif
        @if(!empty($post->tags))
            &middot;
            <span class="tags">
                @foreach($post->tags as $tag)
                    <span>{{ $tag }}</span>
                @endforeach
            </span>
        @endif
    </div>

    <div class="content">
        {!! $post->html !!}
    </div>

    <nav class="pager">
        <span>@if($previous)&larr; <a href="/blog/{{ $previous->slug }}">{{ $previous->title }}</a>@endif</span>
        <span>@if($next)<a href="/blog/{{ $next->slug }}">{{ $next->title }}</a> &rarr;@endif</span>
    </nav>

    @if($related->isNotEmpty())
        <section class="related">
            <h3>Related</h3>
            <ul>
                @foreach($related as $other)
                    <li><a href="/blog/{{ $other->slug }}">{{ $other->title }}</a> <span class="meta">&middot; {{ $other->readingTime }} min</span></li>
                @endforeach
            </ul>
        </section>
    @endif
</body>
</html>
