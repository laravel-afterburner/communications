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

    public function test_archived_threads_appear_on_index_with_archived_badge(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $this->createDiscussionThread($team, $user, ['title' => 'Active thread']);
        $this->createDiscussionThread($team, $user, [
            'title' => 'Archived thread',
            'archived_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Active thread')
            ->assertSee('Archived thread');
    }

    public function test_index_search_filters_threads_by_title(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $this->createDiscussionThread($team, $user, ['title' => 'Parking rules']);
        $this->createDiscussionThread($team, $user, ['title' => 'Garden maintenance']);

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->set('search', 'Parking')
            ->assertSee('Parking rules')
            ->assertDontSee('Garden maintenance');
    }

    public function test_index_search_includes_matching_posts_with_thread_name(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user, ['title' => 'Noise complaints']);
        DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => 'Please keep music down after 10pm.',
        ]);

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->set('search', 'music')
            ->assertSee('Matching posts')
            ->assertSee('Please keep music down after 10pm.')
            ->assertSee('Noise complaints');
    }

    public function test_index_paginates_threads(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);

        for ($i = 1; $i <= 16; $i++) {
            $thread = $this->createDiscussionThread($team, $user, [
                'title' => sprintf('Paginated thread %02d', $i),
            ]);
            $thread->forceFill(['updated_at' => now()->subMinutes(16 - $i)])->save();
        }

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->assertSee('Paginated thread 16')
            ->assertDontSee('Paginated thread 01')
            ->call('gotoPage', 2, 'threadsPage')
            ->assertSee('Paginated thread 01')
            ->assertDontSee('Paginated thread 16');
    }

    public function test_index_search_resets_thread_pagination(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);

        for ($i = 1; $i <= 16; $i++) {
            $thread = $this->createDiscussionThread($team, $user, [
                'title' => sprintf('Paginated thread %02d', $i),
            ]);
            $thread->forceFill(['updated_at' => now()->subMinutes(16 - $i)])->save();
        }

        Livewire::actingAs($user)
            ->test(Index::class, ['team' => $team])
            ->call('gotoPage', 2, 'threadsPage')
            ->assertSee('Paginated thread 01')
            ->set('search', 'Paginated thread 16')
            ->assertSee('Paginated thread 16')
            ->assertDontSee('Paginated thread 01');
    }

    public function test_show_paginates_posts(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $user);

        for ($i = 1; $i <= 20; $i++) {
            DiscussionPost::query()->create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'body' => "Reply {$i}",
            ]);
        }

        Livewire::actingAs($user)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->assertSee('Reply 19')
            ->assertDontSee('Reply 20')
            ->call('gotoPage', 2, 'postsPage')
            ->assertSee('Reply 20')
            ->assertDontSee('Reply 19');
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
