<?php

namespace Afterburner\Communications\Support;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Policies\DiscussionThreadPolicy;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class DiscussionMentionables
{
    /**
     * @return Collection<int, User>
     */
    public static function forThread(DiscussionThread $thread, ?User $excluding = null): Collection
    {
        $thread->loadMissing('team');

        $users = static::teamMembers($thread->team)
            ->filter(fn (User $user) => app(DiscussionThreadPolicy::class)->view($user, $thread));

        return static::excluding($users, $excluding);
    }

    /**
     * @return Collection<int, User>
     */
    public static function forNewThread(Team $team, DiscussionThreadScope $scope, ?User $excluding = null): Collection
    {
        $thread = new DiscussionThread([
            'team_id' => $team->id,
            'scope' => $scope,
        ]);
        $thread->setRelation('team', $team);

        return static::forThread($thread, $excluding);
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    public static function asSelectOptions(Collection $users): Collection
    {
        return $users
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    protected static function excluding(Collection $users, ?User $excluding): Collection
    {
        if ($excluding === null) {
            return $users->values();
        }

        return $users
            ->reject(fn (User $user) => $user->id === $excluding->id)
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    protected static function teamMembers(Team $team): Collection
    {
        if (method_exists($team, 'allUsers')) {
            return $team->allUsers();
        }

        return $team->users()->get();
    }
}
