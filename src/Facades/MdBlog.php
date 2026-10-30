<?php

namespace JCFrane\MdBlog\Facades;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\PostQuery;

/**
 * @method static PostQuery query()
 * @method static Collection<int, Post> all()
 * @method static Post|null find(string $slug)
 * @method static Collection<int, Post> whereTag(string|array $tag)
 * @method static Collection<int, Post> whereCategory(string|array $category)
 * @method static Collection<int, Post> latest()
 * @method static LengthAwarePaginator<int, Post> paginate(int $perPage = 10, string $pageName = 'page', int|null $page = null)
 * @method static Post|null previous(Post $post)
 * @method static Post|null next(Post $post)
 * @method static Collection<int, Post> related(Post $post, int $limit = 3)
 * @method static void clearCache()
 * @method static int sendPost(string $path)
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
