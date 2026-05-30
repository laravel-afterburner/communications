<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Livewire\Discussions\Index;
use Afterburner\Communications\Livewire\Discussions\Show;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

class DiscussionManagementTest extends TestCase
{
    public function test_user_can_quote_and_reply_to_a_post(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user);
        $originalPost = $thread->posts()->first();

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('quotePost', $originalPost->id)
            ->assertSet('quotedPostId', $originalPost->id)
            ->set('replyBody', 'This is my quoted reply.')
            ->call('postReply')
            ->assertSet('quotedPostId', null)
            ->assertSet('replyBody', '');

        $reply = DiscussionPost::query()
            ->where('thread_id', $thread->id)
            ->where('quoted_post_id', $originalPost->id)
            ->first();

        $this->assertNotNull($reply);
        $this->assertSame('This is my quoted reply.', $reply->body);
    }

    public function test_moderator_can_archive_and_restore_thread(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('archiveThread');

        $thread->refresh();
        $this->assertNotNull($thread->archived_at);
        $this->assertFalse($user->can('post', $thread));

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('unarchiveThread');

        $thread->refresh();
        $this->assertNull($thread->archived_at);
        $this->assertTrue($user->can('post', $thread));
    }

    public function test_archived_threads_are_hidden_from_active_index_by_default(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $activeThread = $this->createDiscussionThread($team, $user, ['title' => 'Active thread']);
        $archivedThread = $this->createDiscussionThread($team, $user, [
            'title' => 'Archived thread',
            'archived_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Active thread')
            ->assertDontSee('Archived thread')
            ->set('archiveFilter', 'archived')
            ->assertSee('Archived thread')
            ->assertDontSee('Active thread');
    }

    public function test_author_can_edit_own_post(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user);
        $post = $thread->posts()->first();

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('editPost', $post->id)
            ->set('editPostBody', 'Updated post body')
            ->call('updatePost');

        $post->refresh();
        $this->assertSame('Updated post body', $post->body);
        $this->assertNotNull($post->edited_at);
    }

    public function test_moderator_can_update_and_delete_thread(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('editThread')
            ->set('editThreadForm.title', 'Renamed thread')
            ->call('updateThread');

        $thread->refresh();
        $this->assertSame('Renamed thread', $thread->title);

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('deleteThread');

        $this->assertDatabaseMissing('discussion_threads', ['id' => $thread->id]);
    }

    public function test_member_without_permission_cannot_delete_thread(): void
    {
        [$owner, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $owner);

        $this->assertFalse($member->can('delete', $thread));
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
