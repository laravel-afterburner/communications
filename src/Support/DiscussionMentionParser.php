<?php

namespace Afterburner\Communications\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class DiscussionMentionParser
{
    /**
     * @param  Collection<int, User>  $mentionableUsers
     * @return Collection<int, User>
     */
    public static function parse(string $body, Collection $mentionableUsers): Collection
    {
        if ($body === '' || $mentionableUsers->isEmpty()) {
            return collect();
        }

        $sortedUsers = $mentionableUsers
            ->sortByDesc(fn (User $user) => mb_strlen($user->name))
            ->values();

        $mentioned = collect();
        $offset = 0;

        while (($atPos = mb_strpos($body, '@', $offset)) !== false) {
            $matchedUser = null;

            foreach ($sortedUsers as $user) {
                $needle = '@'.$user->name;

                if (mb_substr($body, $atPos, mb_strlen($needle)) === $needle) {
                    $nextChar = mb_substr($body, $atPos + mb_strlen($needle), 1);

                    if ($nextChar === '' || preg_match('/[\s.,!?;:)\]]/u', $nextChar)) {
                        $matchedUser = $user;
                        $offset = $atPos + mb_strlen($needle);

                        break;
                    }
                }
            }

            if ($matchedUser === null) {
                $offset = $atPos + 1;

                continue;
            }

            if (! $mentioned->contains('id', $matchedUser->id)) {
                $mentioned->push($matchedUser);
            }
        }

        return $mentioned->values();
    }
}
