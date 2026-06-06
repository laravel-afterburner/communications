<?php

namespace Afterburner\Communications\Enums;

use Afterburner\Support\EntityLabel;

enum DiscussionThreadScope: string
{
    case Council = 'council';
    case Team = 'team';
    case Property = 'property';

    public function label(): string
    {
        return match ($this) {
            self::Council => 'Council',
            self::Team => EntityLabel::singularTitle().' (all members)',
            self::Property => 'Property',
        };
    }
}
