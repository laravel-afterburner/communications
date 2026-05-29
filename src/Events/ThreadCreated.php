<?php

namespace Afterburner\Communications\Events;

use Afterburner\Communications\Models\DiscussionThread;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThreadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public DiscussionThread $thread,
    ) {}
}
