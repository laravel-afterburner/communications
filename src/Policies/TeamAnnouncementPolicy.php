<?php

namespace Afterburner\Communications\Policies;

use Afterburner\Communications\Models\TeamAnnouncement;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use Afterburner\Communications\Support\TeamPermissionGate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamAnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Team $team): bool
    {
        if (! $user->belongsToTeam($team)
            || (int) $user->currentTeam?->id !== (int) $team->id) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return TeamPermissionGate::allowsAny($user, $team->id, [
            'view_announcements',
            'post_announcements',
            'manage_announcements',
        ]);
    }

    public function create(User $user, Team $team): bool
    {
        if (! $this->viewAny($user, $team)) {
            return false;
        }

        return TeamPermissionGate::allows($user, $team->id, 'post_announcements');
    }

    public function update(User $user, TeamAnnouncement $announcement): bool
    {
        if (! $this->viewAny($user, $announcement->team)) {
            return false;
        }

        return (int) $announcement->created_by === (int) $user->id
            || TeamPermissionGate::allows($user, $announcement->team_id, 'post_announcements');
    }

    public function delete(User $user, TeamAnnouncement $announcement): bool
    {
        return $this->update($user, $announcement);
    }
}
