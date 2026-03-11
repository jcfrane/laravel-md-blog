<?php

namespace JCFrane\MdBlog\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use JCFrane\MdBlog\Contracts\EmailRecipient;
use JCFrane\MdBlog\Mail\PostMail;
use JCFrane\MdBlog\Post;
use JCFrane\MdBlog\PostRepository;
use Symfony\Component\Finder\Finder;

class PostMailService
{
    public function __construct(
        private readonly PostRepository $repository,
    ) {}

    public function send(string $path): int
    {
        $this->validateConfig();

        $posts = $this->resolvePosts($path);

        if ($posts->isEmpty()) {
            throw new InvalidArgumentException("No posts found at path: {$path}");
        }

        $recipientClass = config('md-blog.mail.recipient_model');
        $chunkSize = (int) config('md-blog.mail.chunk_size', 50);
        $shouldQueue = (bool) config('md-blog.mail.queue', false);
        $queueConnection = config('md-blog.mail.queue_connection');
        $queueName = config('md-blog.mail.queue_name');

        $totalSent = 0;

        foreach ($posts as $post) {
            $recipientClass::emailRecipients()
                ->filter(fn (EmailRecipient $recipient) => $recipient->shouldReceivePostEmail($post))
                ->chunk($chunkSize)
                ->each(function ($chunk) use ($post, $shouldQueue, $queueConnection, $queueName, &$totalSent) {
                    foreach ($chunk as $recipient) {
                        $mailable = (new PostMail($post))
                            ->to($recipient->getEmailAddress(), $recipient->getEmailName());

                        if ($shouldQueue) {
                            if ($queueConnection) {
                                $mailable->onConnection($queueConnection);
                            }
                            if ($queueName) {
                                $mailable->onQueue($queueName);
                            }
                            Mail::queue($mailable);
                        } else {
                            Mail::send($mailable);
                        }

                        $totalSent++;
                    }
                });
        }

        return $totalSent;
    }

    private function validateConfig(): void
    {
        if (! config('md-blog.mail.enabled', false)) {
            throw new InvalidArgumentException('Mail feature is not enabled. Set MD_BLOG_MAIL_ENABLED=true in your environment.');
        }

        $recipientClass = config('md-blog.mail.recipient_model');

        if (! $recipientClass) {
            throw new InvalidArgumentException('No recipient model configured. Set md-blog.mail.recipient_model in your config.');
        }

        if (! class_exists($recipientClass)) {
            throw new InvalidArgumentException("Recipient model class [{$recipientClass}] does not exist.");
        }

        if (! is_subclass_of($recipientClass, EmailRecipient::class)) {
            throw new InvalidArgumentException(
                "Recipient model [{$recipientClass}] must implement " . EmailRecipient::class
            );
        }
    }

    /**
     * @return Collection<int, Post>
     */
    private function resolvePosts(string $path): Collection
    {
        $resolvedPath = $this->resolvePath($path);

        if (is_file($resolvedPath)) {
            $post = $this->repository->findByPath($resolvedPath);

            return $post ? collect([$post]) : collect();
        }

        if (is_dir($resolvedPath)) {
            $finder = (new Finder())
                ->files()
                ->name('*.md')
                ->in($resolvedPath);

            $posts = collect();

            foreach ($finder as $file) {
                $post = $this->repository->findByPath($file->getRealPath());
                if ($post !== null) {
                    $posts->push($post);
                }
            }

            return $posts;
        }

        return collect();
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        return base_path($path);
    }
}
