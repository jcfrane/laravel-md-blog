<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use JCFrane\MdBlog\Mail\PostMail;
use JCFrane\MdBlog\Tests\TestCase;

class SendPostMailCommandTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
        $app['config']->set('md-blog.mail.enabled', true);
        $app['config']->set('md-blog.mail.recipient_model', FakeRecipient::class);
    }

    public function test_command_sends_mail_and_outputs_count(): void
    {
        Mail::fake();

        $this->artisan('md-blog:send-mail', ['path' => $this->fixturesPath('hello-world.md')])
            ->expectsOutputToContain('Successfully sent/queued 2 email(s)')
            ->assertSuccessful();

        Mail::assertSent(PostMail::class, 2);
    }

    public function test_command_fails_when_mail_disabled(): void
    {
        $this->app['config']->set('md-blog.mail.enabled', false);

        $this->artisan('md-blog:send-mail', ['path' => $this->fixturesPath('hello-world.md')])
            ->expectsOutputToContain('Mail feature is not enabled')
            ->assertFailed();
    }

    public function test_command_fails_for_invalid_path(): void
    {
        Mail::fake();

        $this->artisan('md-blog:send-mail', ['path' => '/nonexistent/post.md'])
            ->expectsOutputToContain('No posts found')
            ->assertFailed();
    }
}
