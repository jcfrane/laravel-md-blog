<?php

namespace JCFrane\MdBlog\Contracts;

use Illuminate\Support\LazyCollection;
use JCFrane\MdBlog\Post;

interface EmailRecipient
{
    /** Get all model instances that could receive blog emails. */
    public static function emailRecipients(): LazyCollection;

    /** Per-instance check: should this recipient get this specific post? */
    public function shouldReceivePostEmail(Post $post): bool;

    /** Email address for the To header. */
    public function getEmailAddress(): string;

    /** Display name for the To header. */
    public function getEmailName(): string;
}
