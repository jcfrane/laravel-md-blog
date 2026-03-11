<?php

namespace JCFrane\MdBlog\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use JCFrane\MdBlog\Services\PostMailService;

class SendPostMailCommand extends Command
{
    protected $signature = 'md-blog:send-mail {path : Path to a .md file or directory of posts}';

    protected $description = 'Send blog post(s) as email to configured recipients';

    public function handle(PostMailService $service): int
    {
        $path = $this->argument('path');

        try {
            $count = $service->send($path);

            $this->info("Successfully sent/queued {$count} email(s).");

            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
