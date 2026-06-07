<?php

namespace Afterburner\Communications\Support;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Policies\DiscussionThreadPolicy;
use App\Models\User;

final class DiscussionReactionEligibility
{
    public static function canReact(?User $user, DiscussionPost $post): bool
    {
        return $user?->can('react', $post) ?? false;
    }

    public static function denialMessage(?User $user, DiscussionPost $post): ?string
    {
        if ($user === null || self::canReact($user, $post)) {
            return null;
        }

        $thread = $post->thread;

        if ((int) $post->user_id === (int) $user->id) {
            return __('You cannot react to your own post.');
        }

        if ($thread->isArchived()) {
            return __('Reactions are disabled on archived discussions.');
        }

        if ($thread->isLocked()) {
            return __('Reactions are disabled on locked discussions.');
        }

        if (! $user->belongsToTeam($thread->team)
            || (int) $user->currentTeam?->id !== (int) $thread->team_id) {
            return __('You cannot react to this post.');
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return __('Reactions are not available for this team.');
        }

        if (! app(DiscussionThreadPolicy::class)->view($user, $thread)) {
            return __('You cannot react to this post.');
        }

        return __('You cannot react to this post.');
    }
}
