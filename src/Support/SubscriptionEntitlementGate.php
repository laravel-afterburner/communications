<?php

namespace Afterburner\Communications\Support;

use App\Support\Concerns\ChecksOptionalSubscriptionEntitlement;
use Illuminate\Database\Eloquent\Model;

final class SubscriptionEntitlementGate
{
    use ChecksOptionalSubscriptionEntitlement;

    public const FEATURE_SLUG = 'communications';

    public static function allows(Model $team): bool
    {
        return self::allowsSubscriptionFeature($team, self::FEATURE_SLUG);
    }

    public static function withinLimit(Model $team, string $key, int $current): bool
    {
        return self::withinSubscriptionLimit($team, self::FEATURE_SLUG, $key, $current);
    }
}
