<?php

namespace Afterburner\Communications\Support;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;

class DiscussionPostUrl
{
    public static function forPost(DiscussionPost $post): string
    {
        $post->loadMissing('thread');

        $url = route('teams.discussions.show', [
            'team' => $post->thread->team_id,
            'thread' => $post->thread_id,
            'post' => $post->id,
        ]);

        return $url.'#post-'.$post->id;
    }

    public static function pageForPost(DiscussionThread $thread, int $postId, int $perPage = 20): ?int
    {
        $postIds = DiscussionPost::query()
            ->where('thread_id', $thread->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id');

        $index = $postIds->search($postId);

        if ($index === false) {
            return null;
        }

        return (int) floor($index / $perPage) + 1;
    }
}
