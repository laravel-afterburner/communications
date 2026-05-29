<?php

namespace App\Models;

use App\Traits\SimulatesSubscriptionEntitlements;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SubscribedTeam extends Team
{
    use SimulatesSubscriptionEntitlements;

    protected $table = 'teams';

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id');
    }
}
