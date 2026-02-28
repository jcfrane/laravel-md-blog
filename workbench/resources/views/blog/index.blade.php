<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; line-height: 1.6; }
        h1 { border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
        article { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #f3f4f6; }
        article h2 { margin-bottom: 0.25rem; }
        article h2 a { color: #2563eb; text-decoration: none; }
        article h2 a:hover { text-decoration: underline; }
        .meta { color: #6b7280; font-size: 0.875rem; }
        .tags span { background: #f3f4f6; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; margin-right: 0.25rem; }
    </style>
</head>
<body>
    <h1>Blog</h1>

    @forelse($posts as $post)
        <article>
            <h2><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h2>
            <p class="meta">
                {{ $post->date->format('F j, Y') }}
                @if($post->category)
                    &middot; {{ $post->category }}
                @endif
            </p>
            @if($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif
            @if(!empty($post->tags))
                <div class="tags">
                    @foreach($post->tags as $tag)
                        <span>{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
        </article>
    @empty
        <p>No posts yet.</p>
    @endforelse
</body>
</html>
