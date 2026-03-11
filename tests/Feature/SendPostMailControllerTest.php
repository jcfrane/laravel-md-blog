<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use JCFrane\MdBlog\Mail\PostMail;
use JCFrane\MdBlog\Tests\TestCase;

class SendPostMailControllerTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
        $app['config']->set('md-blog.mail.enabled', true);
        $app['config']->set('md-blog.mail.recipient_model', FakeRecipient::class);
        $app['config']->set('md-blog.mail.middleware', []);
    }

    public function test_route_sends_mail_and_returns_json(): void
    {
        Mail::fake();

        $response = $this->postJson(route('md-blog.send-mail'), [
            'path' => $this->fixturesPath('hello-world.md'),
        ]);

        $response->assertOk()
            ->assertJson([
                'count' => 2,
            ]);

        Mail::assertSent(PostMail::class, 2);
    }

    public function test_route_requires_path(): void
    {
        $response = $this->postJson(route('md-blog.send-mail'), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['path']);
    }

    public function test_route_returns_error_for_invalid_path(): void
    {
        Mail::fake();

        $response = $this->postJson(route('md-blog.send-mail'), [
            'path' => '/nonexistent/post.md',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error']);
    }
}
