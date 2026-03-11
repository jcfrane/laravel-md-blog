<?php

namespace JCFrane\MdBlog\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\LazyCollection;
use JCFrane\MdBlog\Contracts\EmailRecipient;
use JCFrane\MdBlog\Mail\PostMail;
use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\Services\PostMailService;
use JCFrane\MdBlog\Tests\TestCase;
use JCFrane\MdBlog\Traits\ReceivesPostMail;
use InvalidArgumentException;

class FakeRecipient implements EmailRecipient
{
    use ReceivesPostMail;

    public function __construct(
        public string $email,
        public string $name,
        public bool $subscribed = true,
    ) {}

    public static function emailRecipients(): LazyCollection
    {
        return new LazyCollection([
            new static('alice@example.com', 'Alice', true),
            new static('bob@example.com', 'Bob', true),
            new static('charlie@example.com', 'Charlie', false),
        ]);
    }

    public function shouldReceivePostEmail(Post $post): bool
    {
        return $this->subscribed;
    }

    // cursor() is not available on a plain class, so we override emailRecipients above
    public static function cursor(): LazyCollection
    {
        return static::emailRecipients();
    }
}

class PostMailTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('md-blog.path', $this->fixturesPath());
        $app['config']->set('md-blog.cache.enabled', false);
        $app['config']->set('md-blog.mail.enabled', true);
        $app['config']->set('md-blog.mail.recipient_model', FakeRecipient::class);
    }

    public function test_send_mail_for_single_post(): void
    {
        Mail::fake();

        $service = $this->app->make(PostMailService::class);
        $count = $service->send($this->fixturesPath('hello-world.md'));

        $this->assertSame(2, $count); // Alice + Bob (Charlie is unsubscribed)

        Mail::assertSent(PostMail::class, 2);

        Mail::assertSent(PostMail::class, function (PostMail $mail) {
            return $mail->hasTo('alice@example.com') && $mail->post->slug === 'hello-world';
        });

        Mail::assertSent(PostMail::class, function (PostMail $mail) {
            return $mail->hasTo('bob@example.com');
        });
    }

    public function test_send_mail_for_directory(): void
    {
        Mail::fake();

        $service = $this->app->make(PostMailService::class);
        $count = $service->send($this->fixturesPath());

        // 5 posts in fixtures (including draft), 2 eligible recipients each = multiple emails
        $this->assertGreaterThanOrEqual(2, $count);

        Mail::assertSent(PostMail::class);
    }

    public function test_should_receive_post_email_filters_recipients(): void
    {
        Mail::fake();

        $service = $this->app->make(PostMailService::class);
        $service->send($this->fixturesPath('hello-world.md'));

        Mail::assertNotSent(PostMail::class, function (PostMail $mail) {
            return $mail->hasTo('charlie@example.com');
        });
    }

    public function test_throws_when_mail_disabled(): void
    {
        $this->app['config']->set('md-blog.mail.enabled', false);

        $service = $this->app->make(PostMailService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mail feature is not enabled');

        $service->send($this->fixturesPath('hello-world.md'));
    }

    public function test_throws_when_no_recipient_model(): void
    {
        $this->app['config']->set('md-blog.mail.recipient_model', null);

        $service = $this->app->make(PostMailService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No recipient model configured');

        $service->send($this->fixturesPath('hello-world.md'));
    }

    public function test_throws_when_recipient_model_does_not_implement_interface(): void
    {
        $this->app['config']->set('md-blog.mail.recipient_model', \stdClass::class);

        $service = $this->app->make(PostMailService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        $service->send($this->fixturesPath('hello-world.md'));
    }

    public function test_throws_when_path_has_no_posts(): void
    {
        Mail::fake();

        $service = $this->app->make(PostMailService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No posts found');

        $service->send('/nonexistent/path/post.md');
    }

    public function test_mailable_has_correct_subject(): void
    {
        Mail::fake();

        $this->app['config']->set('md-blog.mail.subject_prefix', '[Blog] ');

        $service = $this->app->make(PostMailService::class);
        $service->send($this->fixturesPath('hello-world.md'));

        Mail::assertSent(PostMail::class, function (PostMail $mail) {
            $mail->build();
            return $mail->subject === '[Blog] Hello World';
        });
    }

    public function test_queued_mail_when_queue_enabled(): void
    {
        Mail::fake();

        $this->app['config']->set('md-blog.mail.queue', true);

        $service = $this->app->make(PostMailService::class);
        $service->send($this->fixturesPath('hello-world.md'));

        Mail::assertQueued(PostMail::class, 2);
    }

    public function test_send_post_via_mdblog_class(): void
    {
        Mail::fake();

        $blog = $this->app->make(\JCFrane\MdBlog\MdBlog::class);
        $count = $blog->sendPost($this->fixturesPath('hello-world.md'));

        $this->assertSame(2, $count);
        Mail::assertSent(PostMail::class, 2);
    }
}
