<?php

namespace Afterburner\Communications\Policies;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscussionPostPolicy
{
    use HandlesAuthorization;

    public function update(User $user, DiscussionPost $post): bool
    {
        if (! $this->belongsToPostTeam($user, $post)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($post->thread->team)) {
            return false;
        }

        return (int) $post->user_id === (int) $user->id;
    }

    public function delete(User $user, DiscussionPost $post): bool
    {
        return $this->update($user, $post);
    }

    public function react(User $user, DiscussionPost $post): bool
    {
        if (! $this->belongsToPostTeam($user, $post)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($post->thread->team)) {
            return false;
        }

        $thread = $post->thread;

        if ($thread->isLocked() || $thread->isArchived()) {
            return false;
        }

        if (! app(DiscussionThreadPolicy::class)->view($user, $thread)) {
            return false;
        }

        return (int) $post->user_id !== (int) $user->id;
    }

    protected function belongsToPostTeam(User $user, DiscussionPost $post): bool
    {
        $thread = $post->thread;

        return $user->belongsToTeam($thread->team)
            && (int) $user->currentTeam?->id === (int) $thread->team_id;
    }
}
