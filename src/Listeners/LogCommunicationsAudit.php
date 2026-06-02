<?php

namespace Afterburner\Communications\Listeners;

use Afterburner\Communications\Events\ThreadCreated;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Support\CommunicationsAuditLogger;
use Illuminate\Support\Facades\Auth;

class LogCommunicationsAudit
{
    public function handleThreadCreated(ThreadCreated $event): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        $openingPost = DiscussionPost::query()
            ->where('thread_id', $event->thread->id)
            ->orderBy('id')
            ->value('body');

        CommunicationsAuditLogger::threadCreated($event->thread, $actor, $openingPost);
    }
}
