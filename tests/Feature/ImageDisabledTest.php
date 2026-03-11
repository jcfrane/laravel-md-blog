<?php

namespace JCFrane\MdBlog\Tests\Feature;

use JCFrane\MdBlog\Tests\TestCase;

class ImageDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
        $app['config']->set('md-blog.images.enabled', false);
        $app['config']->set('md-blog.route_prefix', 'md-blog');
    }

    public function test_route_not_registered_when_images_disabled(): void
    {
        $route = $this->app['router']->getRoutes()->getByName('md-blog.image');

        $this->assertNull($route);
    }
}
