<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Enums\DiscussionPostReactionType;
use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Livewire\Discussions\Show;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionPostReaction;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\DiscussionPermissions;
use Afterburner\Communications\Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

class DiscussionReactionsTest extends TestCase
{
    public function test_user_can_add_and_remove_thumbs_up_reaction(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up');

        $reaction = DiscussionPostReaction::query()
            ->where('discussion_post_id', $post->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($reaction);
        $this->assertSame(DiscussionPostReactionType::Up, $reaction->type);

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up');

        $this->assertDatabaseMissing('discussion_post_reactions', [
            'discussion_post_id' => $post->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_user_can_switch_from_thumbs_up_to_thumbs_down(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up')
            ->call('toggleReaction', $post->id, 'down');

        $reaction = DiscussionPostReaction::query()
            ->where('discussion_post_id', $post->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($reaction);
        $this->assertSame(DiscussionPostReactionType::Down, $reaction->type);
    }

    public function test_user_cannot_react_to_own_post(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        $this->assertFalse($author->can('react', $post));

        Livewire::actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up')
            ->assertForbidden();
    }

    public function test_user_cannot_react_in_archived_thread(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author, ['archived_at' => now()]);
        $post = $thread->posts()->first();

        $this->assertFalse($member->can('react', $post));

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up')
            ->assertForbidden();
    }

    public function test_user_cannot_react_in_locked_thread(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author, ['locked_at' => now()]);
        $post = $thread->posts()->first();

        $this->assertFalse($member->can('react', $post));

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $post->id, 'up')
            ->assertForbidden();
    }

    public function test_reaction_counts_are_visible_to_thread_viewers(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $post = $thread->posts()->first();

        DiscussionPostReaction::query()->create([
            'discussion_post_id' => $post->id,
            'user_id' => $member->id,
            'type' => DiscussionPostReactionType::Up,
        ]);

        Livewire::actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->assertDontSee('wire:click="toggleReaction')
            ->assertSee('aria-label="1 thumbs up"', false);
    }

    public function test_reaction_must_belong_to_thread(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, [DiscussionPermissions::VIEW], 'member@example.com');
        $thread = $this->createDiscussionThread($team, $author);
        $otherThread = $this->createDiscussionThread($team, $author, ['title' => 'Other thread']);
        $foreignPost = $otherThread->posts()->first();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($member)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->call('toggleReaction', $foreignPost->id, 'up');
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
