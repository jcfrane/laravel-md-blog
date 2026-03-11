<?php

namespace JCFrane\MdBlog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use JCFrane\MdBlog\Post;

class PostMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Post $post,
    ) {}

    public function build(): self
    {
        $prefix = config('md-blog.mail.subject_prefix', '');

        return $this->subject($prefix . $this->post->title)
            ->view('md-blog::mail.post')
            ->with(['post' => $this->post]);
    }
}
