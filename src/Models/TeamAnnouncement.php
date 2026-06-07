<?php

namespace Afterburner\Communications\Models;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class TeamAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'message',
        'send_email',
        'published_at',
        'emails_sent_at',
        'target_roles',
        'created_by',
    ];

    protected $casts = [
        'send_email' => 'boolean',
        'published_at' => 'datetime',
        'emails_sent_at' => 'datetime',
        'target_roles' => 'array',
    ];

    /**
     * Get the team that owns this announcement.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created the announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the users who have read this announcement.
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if the announcement is published.
     */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    /**
     * Check if the announcement is scheduled for future publication.
     */
    public function isScheduled(): bool
    {
        return $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Check if the announcement is a draft (no published_at date).
     */
    public function isDraft(): bool
    {
        return $this->published_at === null;
    }

    /**
     * Check if a user has read this announcement.
     */
    public function hasBeenReadBy(User $user): bool
    {
        return $this->readers()->where('user_id', $user->id)->exists();
    }

    /**
     * Mark announcement as read by a user.
     */
    public function markAsReadBy(User $user): void
    {
        if (! $this->hasBeenReadBy($user)) {
            $this->readers()->attach($user->id, [
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Scope to get only published announcements.
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope to get announcements for specific roles.
     */
    public function scopeForRoles($query, array $roleSlugs)
    {
        return $query->where(function ($q) use ($roleSlugs) {
            $q->whereNull('target_roles')
                ->orWhereJsonContains('target_roles', $roleSlugs);
        });
    }

    /**
     * Get unread announcements for a user in their current team.
     */
    public static function getUnreadForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        if (! $user->currentTeam) {
            return collect();
        }

        $userRoleSlugs = $user->roles()
            ->where('team_id', $user->currentTeam->id)
            ->pluck('slug')
            ->toArray();

        return static::published()
            ->where('team_id', $user->currentTeam->id)
            ->whereDoesntHave('readers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where(function ($query) use ($userRoleSlugs) {
                $query->whereNull('target_roles')
                    ->orWhere(function ($q) use ($userRoleSlugs) {
                        if (! empty($userRoleSlugs)) {
                            foreach ($userRoleSlugs as $roleSlug) {
                                $q->orWhereJsonContains('target_roles', $roleSlug);
                            }
                        }
                    });
            })
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Get the count of unread announcements for a user in their current team.
     */
    public static function getUnreadCountForUser(User $user): int
    {
        return static::getUnreadForUser($user)->count();
    }

    /**
     * Get the count of users who have read this announcement.
     */
    public function getReadCount(): int
    {
        return $this->readers()->count();
    }

    /**
     * Get the count of eligible users who should see this announcement.
     */
    public function getEligibleUsersCount(): int
    {
        $allUsers = $this->team->allUsers();

        // If no target roles specified, all team users are eligible
        if ($this->target_roles === null || empty($this->target_roles)) {
            return $allUsers->count();
        }

        // Filter users who have at least one of the target roles
        return $allUsers->filter(function ($user) {
            $userRoleSlugs = $user->roles()
                ->where('team_id', $this->team->id)
                ->pluck('slug')
                ->toArray();

            return ! empty(array_intersect($this->target_roles, $userRoleSlugs));
        })->count();
    }

    /**
     * Get the eligible users who should see this announcement.
     *
     * @return Collection
     */
    public function getEligibleUsers()
    {
        $allUsers = $this->team->allUsers();

        // If no target roles specified, all team users are eligible
        if ($this->target_roles === null || empty($this->target_roles)) {
            return $allUsers;
        }

        // Filter users who have at least one of the target roles
        return $allUsers->filter(function ($user) {
            $userRoleSlugs = $user->roles()
                ->where('team_id', $this->team->id)
                ->pluck('slug')
                ->toArray();

            return ! empty(array_intersect($this->target_roles, $userRoleSlugs));
        });
    }

    /**
     * Get users who have read this announcement.
     *
     * @return Collection
     */
    public function getReaders()
    {
        return $this->readers;
    }

    /**
     * Get users who haven't read this announcement.
     *
     * @return Collection
     */
    public function getNonReaders()
    {
        $eligibleUsers = $this->getEligibleUsers();
        $readerIds = $this->readers->pluck('id')->toArray();

        return $eligibleUsers->reject(function ($user) use ($readerIds) {
            return in_array($user->id, $readerIds);
        });
    }
}
