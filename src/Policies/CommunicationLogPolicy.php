<?php

namespace Afterburner\Communications\Policies;

use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use Afterburner\Communications\Support\TeamPermissionGate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommunicationLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Team $team): bool
    {
        if (! $user->belongsToTeam($team) || (int) $user->currentTeam?->id !== (int) $team->id) {
            return false;
        }

        if (! SubscriptionEntitlementGate::allows($team)) {
            return false;
        }

        return TeamPermissionGate::allows($user, $team->id, 'view_communication_log');
    }
}
