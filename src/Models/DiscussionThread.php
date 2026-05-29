<?php

namespace Afterburner\Communications\Models;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Support\CouncilRoleChecker;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DiscussionThread extends Model
{
    protected $fillable = [
        'team_id',
        'title',
        'scope',
        'property_id',
        'created_by',
        'locked_at',
    ];

    protected $casts = [
        'scope' => DiscussionThreadScope::class,
        'locked_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function property(): BelongsTo
    {
        $model = config('afterburner-communications.property_model', \App\Models\Property::class);

        return $this->belongsTo($model, 'property_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class, 'thread_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    /**
     * @param  Builder<DiscussionThread>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user, int $teamId): Builder
    {
        if (CouncilRoleChecker::isCouncilMember($user, $teamId)) {
            return $query;
        }

        return $query->where('scope', '!=', DiscussionThreadScope::Council->value);
    }
}
