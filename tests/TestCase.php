<?php

namespace JCFrane\MdBlog\Tests;

use JCFrane\MdBlog\MdBlogServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            MdBlogServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'MdBlog' => \JCFrane\MdBlog\Facades\MdBlog::class,
        ];
    }

    protected function fixturesPath(string $path = ''): string
    {
        return __DIR__ . '/fixtures/blog' . ($path ? '/' . $path : '');
    }
}
