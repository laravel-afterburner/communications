<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Livewire\Discussions\Show;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\DiscussionPermissions;
use Afterburner\Communications\Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

class DiscussionPermissionsTest extends TestCase
{
    public function test_member_without_permissions_cannot_moderate_threads(): void
    {
        [$owner, $team] = $this->createTeamWithUser();
        $member = $this->createAdditionalUser($team, [], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $owner);

        $this->assertFalse($member->can('update', $thread));
        $this->assertFalse($member->can('archive', $thread));
        $this->assertFalse($member->can('lock', $thread));
        $this->assertFalse($member->can('delete', $thread));
    }

    public function test_member_with_archive_permission_can_archive_but_not_delete_thread(): void
    {
        [$owner, $team] = $this->createTeamWithUser();
        $moderator = $this->createAdditionalUser($team, [DiscussionPermissions::ARCHIVE], 'archive@example.com');
        $thread = $this->createDiscussionThread($team, $owner);

        $this->assertTrue($moderator->can('archive', $thread));
        $this->assertFalse($moderator->can('delete', $thread));
        $this->assertFalse($moderator->can('update', $thread));
        $this->assertFalse($moderator->can('lock', $thread));
    }

    public function test_member_can_edit_own_post_but_not_other_members_posts(): void
    {
        [$author, $team] = $this->createTeamWithUser();
        $member = $this->createAdditionalUser($team, [], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        $this->assertTrue($author->can('update', $post));
        $this->assertFalse($member->can('update', $post));
        $this->assertFalse($member->can('delete', $post));
    }

    public function test_moderator_can_edit_other_members_posts_with_permission(): void
    {
        [$author, $team] = $this->createTeamWithUser();
        $moderator = $this->createAdditionalUser($team, [DiscussionPermissions::MODERATE_POSTS], 'moderator@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        $this->assertTrue($moderator->can('update', $post));
        $this->assertTrue($moderator->can('delete', $post));
    }

    public function test_legacy_manage_discussions_permission_still_grants_full_access(): void
    {
        [$owner, $team] = $this->createTeamWithUser();
        $legacyModerator = $this->createAdditionalUser($team, [DiscussionPermissions::LEGACY_MANAGE], 'legacy@example.com');
        $thread = $this->createDiscussionThread($team, $owner);
        $post = $thread->posts()->first();

        $this->assertTrue($legacyModerator->can('update', $thread));
        $this->assertTrue($legacyModerator->can('archive', $thread));
        $this->assertTrue($legacyModerator->can('lock', $thread));
        $this->assertTrue($legacyModerator->can('delete', $thread));
        $this->assertTrue($legacyModerator->can('update', $post));
    }

    public function test_member_with_create_permission_can_start_threads(): void
    {
        [$owner, $team] = $this->createTeamWithUser();
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::CREATE], 'creator@example.com');

        $this->assertTrue($member->can('create', [DiscussionThread::class, $team]));
    }

    public function test_archive_action_requires_archive_permission(): void
    {
        [$owner, $team] = $this->createTeamWithUser();
        $moderator = $this->createAdditionalUser($team, [DiscussionPermissions::ARCHIVE], 'archive@example.com');
        $thread = $this->createDiscussionThread($team, $owner);

        Livewire::actingAs($moderator)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('archiveThread');

        $thread->refresh();
        $this->assertNotNull($thread->archived_at);
    }

    protected function createDiscussionThread(Team $team, User $user, array $overrides = []): DiscussionThread
    {
        $thread = DiscussionThread::query()->create(array_merge([
            'team_id' => $team->id,
            'title' => 'Test Thread',
            'scope' => DiscussionThreadScope::Team,
            'created_by' => $user->id,
        ], $overrides));

        DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => 'Opening post body',
        ]);

        return $thread->fresh(['posts']);
    }
}
