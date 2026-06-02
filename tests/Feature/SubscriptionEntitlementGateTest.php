<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\DiscussionPermissions;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use Afterburner\Communications\Tests\TestCase;
use App\Models\SubscribedTeam;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionEntitlementGateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['afterburner-subscriptions.enabled' => true]);
    }

    public function test_access_allowed_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [$user, $team] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => []],
            trialEndsAt: null,
        );

        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
        $this->assertTrue($user->can('viewAny', [DiscussionThread::class, $team]));
        $this->assertTrue($user->can('create', [DiscussionThread::class, $team]));
    }

    public function test_access_allowed_when_team_does_not_implement_subscription_methods(): void
    {
        [$user, $team] = $this->createTeamWithUser([DiscussionPermissions::CREATE]);

        $this->assertFalse(method_exists($team, 'hasEntitlement'));
        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
        $this->assertTrue($user->can('viewAny', [DiscussionThread::class, $team]));
        $this->assertTrue($user->can('create', [DiscussionThread::class, $team]));
    }

    public function test_access_allowed_during_generic_trial_without_plan_feature(): void
    {
        [$user, $team] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => []],
            trialEndsAt: now()->addWeek(),
        );

        $this->assertTrue($team->onGenericTrial());
        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
        $this->assertTrue($user->can('viewAny', [DiscussionThread::class, $team]));
        $this->assertTrue($user->can('create', [DiscussionThread::class, $team]));
    }

    public function test_access_denied_after_trial_when_plan_lacks_communications_feature(): void
    {
        [$user, $team] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => ['documents']],
            trialEndsAt: now()->subDay(),
        );

        $this->assertFalse($team->onGenericTrial());
        $this->assertFalse(SubscriptionEntitlementGate::allows($team));
        $this->assertFalse($user->can('viewAny', [DiscussionThread::class, $team]));
        $this->assertFalse($user->can('create', [DiscussionThread::class, $team]));
    }

    public function test_access_allowed_after_trial_when_plan_includes_communications_feature(): void
    {
        [$user, $team] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => ['communications']],
            trialEndsAt: now()->subDay(),
        );

        $this->assertFalse($team->onGenericTrial());
        $this->assertTrue(SubscriptionEntitlementGate::allows($team));
        $this->assertTrue($user->can('viewAny', [DiscussionThread::class, $team]));
        $this->assertTrue($user->can('create', [DiscussionThread::class, $team]));
    }

    public function test_thread_view_denied_without_entitlement_even_with_permission(): void
    {
        [$creator, $team] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => ['communications']],
            trialEndsAt: now()->addWeek(),
        );

        $thread = DiscussionThread::query()->create([
            'team_id' => $team->id,
            'title' => 'Team thread',
            'scope' => DiscussionThreadScope::Team,
            'created_by' => $creator->id,
        ]);

        $team->simulatePlanFeatures(['features' => []]);
        $team->forceFill(['trial_ends_at' => now()->subDay()])->save();

        $deniedTeam = SubscribedTeam::query()->findOrFail($team->id);
        $deniedTeam->simulatePlanFeatures(['features' => []]);
        $thread->setRelation('team', $deniedTeam);

        [$member] = $this->createSubscribedTeamWithUser(
            permissions: [DiscussionPermissions::CREATE],
            planFeatures: ['features' => []],
            trialEndsAt: now()->subDay(),
            existingTeam: $team,
        );

        $this->assertFalse($member->can('view', $thread));
        $this->assertFalse($member->can('post', $thread));
    }

    public function test_within_limit_bypasses_when_subscriptions_disabled(): void
    {
        config(['afterburner-subscriptions.enabled' => false]);

        [, $team] = $this->createSubscribedTeamWithUser(
            planFeatures: ['max_discussion_threads' => 1],
            trialEndsAt: null,
        );

        $this->assertTrue(SubscriptionEntitlementGate::withinLimit($team, 'max_discussion_threads', 99));
    }

    public function test_within_limit_allowed_during_trial(): void
    {
        [, $team] = $this->createSubscribedTeamWithUser(
            planFeatures: ['max_discussion_threads' => 1],
            trialEndsAt: now()->addWeek(),
        );

        $this->assertTrue(SubscriptionEntitlementGate::withinLimit($team, 'max_discussion_threads', 99));
    }

    public function test_within_limit_denied_when_exceeded_after_trial(): void
    {
        [, $team] = $this->createSubscribedTeamWithUser(
            planFeatures: ['max_discussion_threads' => 2],
            trialEndsAt: now()->subDay(),
        );

        $this->assertTrue(SubscriptionEntitlementGate::withinLimit($team, 'max_discussion_threads', 2));
        $this->assertFalse(SubscriptionEntitlementGate::withinLimit($team, 'max_discussion_threads', 3));
    }

    /**
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $planFeatures
     * @return array{0: User, 1: Team}
     */
    protected function createSubscribedTeamWithUser(
        ?array $permissions = null,
        array $planFeatures = ['features' => ['communications']],
        ?Carbon $trialEndsAt = null,
        ?Team $existingTeam = null,
    ): array {
        if ($existingTeam !== null) {
            $member = $this->createAdditionalUser($existingTeam, $permissions, 'member-'.uniqid().'@example.com');

            if ($existingTeam instanceof SubscribedTeam) {
                $existingTeam->simulatePlanFeatures($planFeatures);
            }

            return [$member, $existingTeam];
        }

        $this->seedPermissions();
        $roleId = $this->createRoleWithPermissions('member', $permissions ?? $this->allDiscussionPermissionSlugs());

        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $team = SubscribedTeam::query()->create([
            'name' => 'Test Team',
            'user_id' => $user->id,
            'trial_ends_at' => $trialEndsAt,
        ]);

        $team->simulatePlanFeatures($planFeatures);
        $team->users()->attach($user);
        $user->update(['current_team_id' => $team->id]);

        DB::table('user_role')->insert([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'team_id' => $team->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$user->fresh(), $team];
    }
}
