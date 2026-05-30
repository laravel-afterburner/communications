<?php

namespace Afterburner\Communications\Policies;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use Afterburner\Communications\Support\TeamPermissionGate;
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

        return (int) $post->user_id === (int) $user->id
            || TeamPermissionGate::allows($user, $post->thread->team_id, 'manage_discussions');
    }

    public function delete(User $user, DiscussionPost $post): bool
    {
        return $this->update($user, $post);
    }

    protected function belongsToPostTeam(User $user, DiscussionPost $post): bool
    {
        $thread = $post->thread;

        return $user->belongsToTeam($thread->team)
            && (int) $user->currentTeam?->id === (int) $thread->team_id;
    }
}
