<?php

namespace Afterburner\Communications\Support;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Models\TeamAnnouncement;
use App\Models\User;
use App\Support\Audit\AuditLogger;

class CommunicationsAuditLogger
{
    public const CATEGORY_DISCUSSION = 'discussion';

    public const CATEGORY_ANNOUNCEMENT = 'announcement';

    public static function threadCreated(DiscussionThread $thread, User $actor, ?string $openingPostBody = null): void
    {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.thread.created',
            auditable: $thread,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} started discussion \"{$thread->title}\".",
                context: [
                    'thread_id' => $thread->id,
                    'title' => $thread->title,
                    'scope' => $thread->scope->value,
                    'opening_post_excerpt' => $openingPostBody ? self::excerpt($openingPostBody) : null,
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $fieldChanges
     */
    public static function threadUpdated(
        DiscussionThread $thread,
        User $actor,
        array $fieldChanges,
    ): void {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.thread.updated',
            auditable: $thread,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} updated discussion \"{$thread->title}\".",
                fieldChanges: $fieldChanges,
                context: ['thread_id' => $thread->id, 'title' => $thread->title],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function threadArchived(DiscussionThread $thread, User $actor, bool $archived): void
    {
        $verb = $archived ? 'archived' : 'restored';

        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: $archived ? 'discussion.thread.archived' : 'discussion.thread.unarchived',
            auditable: $thread,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} {$verb} discussion \"{$thread->title}\".",
                context: ['thread_id' => $thread->id, 'title' => $thread->title],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function threadLocked(DiscussionThread $thread, User $actor, bool $locked): void
    {
        $verb = $locked ? 'locked' : 'unlocked';

        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: $locked ? 'discussion.thread.locked' : 'discussion.thread.unlocked',
            auditable: $thread,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} {$verb} discussion \"{$thread->title}\".",
                context: ['thread_id' => $thread->id, 'title' => $thread->title],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function threadDeleted(DiscussionThread $thread, User $actor, ?string $openingPostExcerpt = null): void
    {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.thread.deleted',
            auditable: $thread,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} deleted discussion \"{$thread->title}\".",
                context: [
                    'thread_id' => $thread->id,
                    'title' => $thread->title,
                    'scope' => $thread->scope->value,
                    'opening_post_excerpt' => $openingPostExcerpt,
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function postCreated(DiscussionPost $post, DiscussionThread $thread, User $actor): void
    {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.post.created',
            auditable: $post,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} replied in \"{$thread->title}\".",
                context: [
                    'thread_id' => $thread->id,
                    'thread_title' => $thread->title,
                    'post_id' => $post->id,
                    'body_excerpt' => self::excerpt($post->body),
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $fieldChanges
     */
    public static function postUpdated(
        DiscussionPost $post,
        DiscussionThread $thread,
        User $actor,
        array $fieldChanges,
    ): void {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.post.updated',
            auditable: $post,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} edited a post in \"{$thread->title}\".",
                fieldChanges: $fieldChanges,
                context: [
                    'thread_id' => $thread->id,
                    'thread_title' => $thread->title,
                    'post_id' => $post->id,
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function postDeleted(DiscussionPost $post, DiscussionThread $thread, User $actor): void
    {
        AuditLogger::log(
            category: self::CATEGORY_DISCUSSION,
            eventName: 'discussion.post.deleted',
            auditable: $post,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} deleted a post in \"{$thread->title}\".",
                context: [
                    'thread_id' => $thread->id,
                    'thread_title' => $thread->title,
                    'post_id' => $post->id,
                    'body_excerpt' => self::excerpt($post->body),
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $thread->team_id,
            actionType: 'livewire',
        );
    }

    public static function announcementCreated(TeamAnnouncement $announcement, User $actor): void
    {
        AuditLogger::log(
            category: self::CATEGORY_ANNOUNCEMENT,
            eventName: 'announcement.created',
            auditable: $announcement,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} created announcement \"{$announcement->title}\".",
                context: [
                    'announcement_id' => $announcement->id,
                    'title' => $announcement->title,
                    'message_excerpt' => self::excerpt($announcement->message),
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $announcement->team_id,
            actionType: 'livewire',
        );
    }

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $fieldChanges
     */
    public static function announcementUpdated(
        TeamAnnouncement $announcement,
        User $actor,
        array $fieldChanges,
    ): void {
        AuditLogger::log(
            category: self::CATEGORY_ANNOUNCEMENT,
            eventName: 'announcement.updated',
            auditable: $announcement,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} updated announcement \"{$announcement->title}\".",
                fieldChanges: $fieldChanges,
                context: ['announcement_id' => $announcement->id, 'title' => $announcement->title],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $announcement->team_id,
            actionType: 'livewire',
        );
    }

    public static function announcementDeleted(TeamAnnouncement $announcement, User $actor): void
    {
        AuditLogger::log(
            category: self::CATEGORY_ANNOUNCEMENT,
            eventName: 'announcement.deleted',
            auditable: $announcement,
            changes: AuditLogger::changesWithSummary(
                summary: "{$actor->name} deleted announcement \"{$announcement->title}\".",
                context: [
                    'announcement_id' => $announcement->id,
                    'title' => $announcement->title,
                    'message_excerpt' => self::excerpt($announcement->message),
                ],
            ),
            metadata: ['actor_user_id' => $actor->id],
            teamId: $announcement->team_id,
            actionType: 'livewire',
        );
    }

    protected static function excerpt(string $body, int $length = 200): string
    {
        $text = trim(strip_tags($body));

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length).'…';
    }
}
