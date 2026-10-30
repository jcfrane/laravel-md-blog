<?php

use Illuminate\Support\Facades\Route;
use JCFrane\MdBlog\Facades\MdBlog;

Route::get('/blog', function () {
    $query = MdBlog::query();

    if ($tag = request('tag')) {
        $query->whereTag($tag);
    }

    return view('workbench::blog.index', [
        'posts' => $query->paginate(3)->withQueryString(),
        'tag' => $tag,
    ]);
});

Route::get('/blog/{slug}', function (string $slug) {
    $post = MdBlog::find($slug);

    if ($post === null) {
        abort(404);
    }

    return view('workbench::blog.show', [
        'post' => $post,
        'previous' => MdBlog::previous($post),
        'next' => MdBlog::next($post),
        'related' => MdBlog::related($post),
    ]);
});
