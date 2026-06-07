<?php

namespace Afterburner\Communications\Enums;

enum DiscussionPostReactionType: string
{
    case Up = 'up';
    case Down = 'down';

    public function label(): string
    {
        return match ($this) {
            self::Up => __('Thumbs up'),
            self::Down => __('Thumbs down'),
        };
    }
}
