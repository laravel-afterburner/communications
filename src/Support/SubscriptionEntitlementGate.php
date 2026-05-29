<?php

namespace Afterburner\Communications\Support;

use Illuminate\Database\Eloquent\Model;

final class SubscriptionEntitlementGate
{
    public const FEATURE_SLUG = 'communications';

    public static function allows(Model $team): bool
    {
        if (! static::subscriptionsEnforcementActive($team)) {
            return true;
        }

        if (static::teamOnGenericTrial($team)) {
            return true;
        }

        return $team->hasEntitlement(static::FEATURE_SLUG);
    }

    public static function withinLimit(Model $team, string $key, int $current): bool
    {
        if (! static::subscriptionsEnforcementActive($team)) {
            return true;
        }

        if (static::teamOnGenericTrial($team)) {
            return true;
        }

        return $team->withinEntitlementLimit($key, $current);
    }

    protected static function subscriptionsEnforcementActive(Model $team): bool
    {
        if (! config('afterburner-subscriptions.enabled', false)) {
            return false;
        }

        return method_exists($team, 'hasEntitlement')
            && method_exists($team, 'onGenericTrial')
            && method_exists($team, 'withinEntitlementLimit');
    }

    protected static function teamOnGenericTrial(Model $team): bool
    {
        return $team->onGenericTrial();
    }
}
