<?php

namespace Afterburner\Communications\Notifications;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Support\DiscussionPostUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiscussionMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public DiscussionPost $post) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->post->loadMissing(['thread', 'user']);

        return [
            'type' => 'discussion_mentioned',
            'thread_id' => $this->post->thread_id,
            'team_id' => $this->post->thread->team_id,
            'thread_title' => $this->post->thread->title,
            'post_id' => $this->post->id,
            'author_name' => $this->post->user->name,
            'message' => __(':author mentioned you in the discussion ":thread".', [
                'author' => $this->post->user->name,
                'thread' => $this->post->thread->title,
            ]),
            'url' => DiscussionPostUrl::forPost($this->post),
        ];
    }
}
