<?php

namespace Afterburner\Communications\Support;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Notifications\DiscussionMentionedNotification;
use Afterburner\Communications\Notifications\DiscussionUpdatedNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DiscussionNotificationService
{
    /**
     * @var array<int, class-string>
     */
    protected const DISCUSSION_NOTIFICATION_TYPES = [
        DiscussionMentionedNotification::class,
        DiscussionUpdatedNotification::class,
    ];

    public function syncMentions(DiscussionPost $post, string $body, DiscussionThread $thread): Collection
    {
        $mentionedUsers = DiscussionMentionParser::parse(
            $body,
            DiscussionMentionables::forThread($thread),
        );

        $post->mentions()->sync($mentionedUsers->pluck('id')->all());

        return $mentionedUsers;
    }

    public function notifyForPost(DiscussionPost $post, User $author): void
    {
        $post->loadMissing(['thread', 'mentions']);

        $thread = $post->thread;
        $mentionedUserIds = $post->mentions->pluck('id')->all();

        $participantIds = DiscussionPost::query()
            ->where('thread_id', $thread->id)
            ->where('id', '!=', $post->id)
            ->distinct()
            ->pluck('user_id')
            ->reject(fn (int $userId) => $userId === $author->id)
            ->values();

        foreach ($mentionedUserIds as $userId) {
            if ((int) $userId === $author->id) {
                continue;
            }

            $user = User::query()->find($userId);

            if ($user === null || ! Gate::forUser($user)->allows('view', $thread)) {
                continue;
            }

            $this->revokeUnreadForThread($user, $thread->id);
            $user->notify(new DiscussionMentionedNotification($post));
        }

        foreach ($participantIds as $userId) {
            if (in_array((int) $userId, $mentionedUserIds, true)) {
                continue;
            }

            $user = User::query()->find($userId);

            if ($user === null || ! Gate::forUser($user)->allows('view', $thread)) {
                continue;
            }

            $this->revokeUnreadForThread($user, $thread->id);
            $user->notify(new DiscussionUpdatedNotification($post));
        }
    }

    public function clearForUserAndThread(User $user, DiscussionThread $thread): void
    {
        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->whereIn('type', self::DISCUSSION_NOTIFICATION_TYPES)
            ->where('data->thread_id', $thread->id)
            ->delete();
    }

    public static function getUnreadCountForUser(User $user): int
    {
        if ($user->currentTeam === null) {
            return 0;
        }

        return $user->unreadNotifications()
            ->whereIn('type', self::DISCUSSION_NOTIFICATION_TYPES)
            ->where('data->team_id', $user->currentTeam->id)
            ->count();
    }

    protected function revokeUnreadForThread(User $user, int $threadId): void
    {
        DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->whereNull('read_at')
            ->whereIn('type', self::DISCUSSION_NOTIFICATION_TYPES)
            ->where('data->thread_id', $threadId)
            ->delete();
    }
}
