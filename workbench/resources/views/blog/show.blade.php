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
    </style>
</head>
<body>
    <a class="back" href="/blog">&larr; Back to blog</a>

    <h1>{{ $post->title }}</h1>

    <div class="meta">
        {{ $post->date->format('F j, Y') }}
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
</body>
</html>
