<?php

namespace Afterburner\Communications\Policies;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\CouncilRoleChecker;
use Afterburner\Communications\Support\DiscussionPermissions;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscussionThreadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Team $team): bool
    {
        if (! $user->belongsToTeam($team)
            || (int) $user->currentTeam?->id !== (int) $team->id) {
            return false;
        }

        return SubscriptionEntitlementGate::allows($team);
    }

    public function view(User $user, DiscussionThread $thread): bool
    {
        if (! $this->belongsToThreadTeam($user, $thread)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return false;
        }

        return $this->canAccessScope($user, $thread);
    }

    public function create(User $user, Team $team): bool
    {
        if (! $this->viewAny($user, $team)) {
            return false;
        }

        return DiscussionPermissions::canCreate($user, $team->id)
            || CouncilRoleChecker::hasCouncilRole($user, $team->id);
    }

    public function update(User $user, DiscussionThread $thread): bool
    {
        if (! $this->belongsToThreadTeam($user, $thread)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return false;
        }

        return DiscussionPermissions::canEdit($user, $thread->team_id);
    }

    public function lock(User $user, DiscussionThread $thread): bool
    {
        if (! $this->belongsToThreadTeam($user, $thread)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return false;
        }

        return DiscussionPermissions::canLock($user, $thread->team_id);
    }

    public function delete(User $user, DiscussionThread $thread): bool
    {
        if (! $this->belongsToThreadTeam($user, $thread)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return false;
        }

        return DiscussionPermissions::canDelete($user, $thread->team_id);
    }

    public function archive(User $user, DiscussionThread $thread): bool
    {
        if (! $this->belongsToThreadTeam($user, $thread)) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($thread->team)) {
            return false;
        }

        return DiscussionPermissions::canArchive($user, $thread->team_id);
    }

    public function post(User $user, DiscussionThread $thread): bool
    {
        if ($thread->isLocked() || $thread->isArchived()) {
            return false;
        }

        return $this->view($user, $thread);
    }

    protected function canAccessScope(User $user, DiscussionThread $thread): bool
    {
        return match ($thread->scope) {
            DiscussionThreadScope::Council => CouncilRoleChecker::isCouncilMember($user, $thread->team_id),
            DiscussionThreadScope::Team => true,
            DiscussionThreadScope::Property => true,
        };
    }

    protected function belongsToThreadTeam(User $user, DiscussionThread $thread): bool
    {
        return $user->belongsToTeam($thread->team)
            && (int) $user->currentTeam?->id === (int) $thread->team_id;
    }
}
