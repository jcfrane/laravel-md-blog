<?php

namespace JCFrane\MdBlog\Traits;

use Illuminate\Support\LazyCollection;

trait ReceivesPostMail
{
    /** Default: returns all model instances via cursor for memory efficiency. */
    public static function emailRecipients(): LazyCollection
    {
        return static::cursor();
    }

    /** Default: uses the model's `email` attribute. */
    public function getEmailAddress(): string
    {
        return $this->email;
    }

    /** Default: uses the model's `name` attribute. */
    public function getEmailName(): string
    {
        return $this->name;
    }
}
