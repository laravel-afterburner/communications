<?php

namespace Afterburner\Communications\Support;

use App\Models\User;
use Illuminate\Support\Collection;

class DiscussionMentionFormatter
{
    /**
     * @param  Collection<int, User>  $mentionedUsers
     */
    public static function format(string $body, Collection $mentionedUsers): string
    {
        if ($body === '' || $mentionedUsers->isEmpty()) {
            return nl2br(e($body));
        }

        $formatted = e($body);

        foreach ($mentionedUsers->sortByDesc(fn (User $user) => mb_strlen($user->name)) as $user) {
            $pattern = '/@'.preg_quote($user->name, '/').'(?![\w])/u';
            $replacement = '<span class="text-indigo-600 dark:text-indigo-400">@'
                .e($user->name).'</span>';
            $formatted = preg_replace($pattern, $replacement, $formatted) ?? $formatted;
        }

        return nl2br($formatted);
    }
}
