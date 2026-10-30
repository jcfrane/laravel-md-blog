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
        .tags a { color: inherit; text-decoration: none; }
        .cover { width: 100%; max-height: 240px; object-fit: cover; border-radius: 0.5rem; margin-bottom: 0.75rem; }
        .pagination { display: flex; gap: 0.5rem; list-style: none; padding: 0; }
        .pagination a, .pagination span { padding: 0.25rem 0.625rem; border: 1px solid #e5e7eb; border-radius: 0.25rem; text-decoration: none; color: #2563eb; }
        .pagination .active span { background: #2563eb; color: #fff; border-color: #2563eb; }
        .pagination .disabled span { color: #9ca3af; }
    </style>
</head>
<body>
    <h1>Blog</h1>

    @if($tag)
        <p class="meta">Posts tagged <strong>{{ $tag }}</strong> &middot; <a href="/blog">all posts</a></p>
    @endif

    @forelse($posts as $post)
        <article>
            @if($post->cover)
                <a href="/blog/{{ $post->slug }}"><img class="cover" src="{{ $post->cover }}" alt=""></a>
            @endif
            <h2><a href="/blog/{{ $post->slug }}">{{ $post->title }}</a></h2>
            <p class="meta">
                {{ $post->date->format('F j, Y') }}
                @if($post->category)
                    &middot; {{ $post->category }}
                @endif
                &middot; {{ $post->readingTime }} min read
            </p>
            @if($post->excerpt)
                <p>{{ $post->excerpt }}</p>
            @endif
            @if(!empty($post->tags))
                <div class="tags">
                    @foreach($post->tags as $postTag)
                        <span><a href="/blog?tag={{ urlencode($postTag) }}">{{ $postTag }}</a></span>
                    @endforeach
                </div>
            @endif
        </article>
    @empty
        <p>No posts yet.</p>
    @endforelse

    @if($posts->hasPages())
        <ul class="pagination">
            @if($posts->onFirstPage())
                <li class="disabled"><span>&laquo;</span></li>
            @else
                <li><a href="{{ $posts->previousPageUrl() }}">&laquo;</a></li>
            @endif
            @foreach($posts->getUrlRange(1, $posts->lastPage()) as $page => $url)
                <li class="{{ $page === $posts->currentPage() ? 'active' : '' }}">
                    @if($page === $posts->currentPage())<span>{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                </li>
            @endforeach
            @if($posts->hasMorePages())
                <li><a href="{{ $posts->nextPageUrl() }}">&raquo;</a></li>
            @else
                <li class="disabled"><span>&raquo;</span></li>
            @endif
        </ul>
    @endif
</body>
</html>
