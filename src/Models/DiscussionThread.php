<?php

namespace Afterburner\Communications\Models;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Support\CouncilRoleChecker;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class DiscussionThread extends Model
{
    protected $fillable = [
        'team_id',
        'title',
        'scope',
        'created_by',
        'locked_at',
        'archived_at',
    ];

    protected $casts = [
        'scope' => DiscussionThreadScope::class,
        'locked_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function propertyModelClass(): ?string
    {
        $model = config('afterburner-communications.property_model');

        return is_string($model) && class_exists($model) ? $model : null;
    }

    public function properties(): BelongsToMany
    {
        $model = static::propertyModelClass();

        if ($model === null) {
            throw new \LogicException('Property model is not configured for discussion threads.');
        }

        return $this->belongsToMany(
            $model,
            'discussion_thread_property',
            'discussion_thread_id',
            'property_id',
        )->withTimestamps();
    }

    /**
     * @return Collection<int, string>
     */
    public function propertyLotLabels(): Collection
    {
        return $this->properties
            ->sortBy('lot_number')
            ->map(fn ($property) => __('Lot').' '.$property->lot_number)
            ->values();
    }

    public function latestReplyAuthorName(): ?string
    {
        $latestPost = $this->relationLoaded('posts')
            ? $this->posts->first()
            : $this->posts()->with('user')->latest()->first();

        return $latestPost?->user?->name;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(DiscussionPost::class, 'thread_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @param  Builder<DiscussionThread>  $query
     */
    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<DiscussionThread>  $query
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
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
