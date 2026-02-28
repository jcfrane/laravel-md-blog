<?php

namespace JCFrane\MdBlog\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use JCFrane\MdBlog\Post;

/**
 * @method static Collection<int, Post> all()
 * @method static Post|null find(string $slug)
 * @method static Collection<int, Post> whereTag(string $tag)
 * @method static Collection<int, Post> whereCategory(string $category)
 * @method static Collection<int, Post> latest()
 * @method static void clearCache()
 *
 * @see \JCFrane\MdBlog\MdBlog
 */
class MdBlog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JCFrane\MdBlog\MdBlog::class;
    }
}
