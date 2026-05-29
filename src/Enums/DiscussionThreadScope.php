<?php

namespace Afterburner\Communications\Enums;

enum DiscussionThreadScope: string
{
    case Council = 'council';
    case Team = 'team';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Council => 'Council',
            self::Team => 'Team',
            self::Property => 'Property',
        };
    }
}
