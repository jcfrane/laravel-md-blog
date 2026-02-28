<?php

use Illuminate\Support\Facades\Route;
use JCFrane\MdBlog\Facades\MdBlog;

Route::get('/blog', function () {
    return view('workbench::blog.index', [
        'posts' => MdBlog::latest(),
    ]);
});

Route::get('/blog/{slug}', function (string $slug) {
    $post = MdBlog::find($slug);

    if ($post === null) {
        abort(404);
    }

    return view('workbench::blog.show', [
        'post' => $post,
    ]);
});
