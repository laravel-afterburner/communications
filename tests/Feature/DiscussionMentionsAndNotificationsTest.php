<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Livewire\Discussions\Show;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Notifications\DiscussionMentionedNotification;
use Afterburner\Communications\Notifications\DiscussionUpdatedNotification;
use Afterburner\Communications\Support\DiscussionMentionables;
use Afterburner\Communications\Support\DiscussionMentionParser;
use Afterburner\Communications\Support\DiscussionPostUrl;
use Afterburner\Communications\Tests\TestCase;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

class DiscussionMentionsAndNotificationsTest extends TestCase
{
    public function test_mention_parser_matches_team_member_names(): void
    {
        [$user, $team] = $this->createTeamWithUser(['manage_discussions']);
        $other = $this->createAdditionalUser($team, [], 'other@example.com');
        $other->update(['name' => 'Jane Smith']);

        $mentions = DiscussionMentionParser::parse(
            'Hello @Jane Smith, please review this.',
            collect([$user, $other]),
        );

        $this->assertCount(1, $mentions);
        $this->assertSame($other->id, $mentions->first()->id);
    }

    public function test_reply_notifies_mentioned_user(): void
    {
        Notification::fake();

        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $mentioned = $this->createAdditionalUser($team, [], 'mentioned@example.com');
        $mentioned->update(['name' => 'Jane Smith']);
        $thread = $this->createDiscussionThread($team, $author);

        Livewire::actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->set('replyBody', 'Hey @Jane Smith, thoughts?')
            ->call('postReply');

        Notification::assertSentTo($mentioned, DiscussionMentionedNotification::class);
        Notification::assertNotSentTo($author, DiscussionMentionedNotification::class);
    }

    public function test_reply_notifies_existing_participants(): void
    {
        Notification::fake();

        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $participant = $this->createAdditionalUser($team, [], 'participant@example.com');
        $thread = $this->createDiscussionThread($team, $author);

        DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $participant->id,
            'body' => 'First reply',
        ]);

        Livewire::actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->set('replyBody', 'Follow-up message')
            ->call('postReply');

        Notification::assertSentTo($participant, DiscussionUpdatedNotification::class);
        Notification::assertNotSentTo($author, DiscussionUpdatedNotification::class);
    }

    public function test_mentioned_participant_receives_mention_notification_only(): void
    {
        Notification::fake();

        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $participant = $this->createAdditionalUser($team, [], 'participant@example.com');
        $participant->update(['name' => 'Jane Smith']);
        $thread = $this->createDiscussionThread($team, $author);

        DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $participant->id,
            'body' => 'First reply',
        ]);

        Livewire::actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->set('replyBody', 'Thanks @Jane Smith')
            ->call('postReply');

        Notification::assertSentTo($participant, DiscussionMentionedNotification::class);
        Notification::assertNotSentTo($participant, DiscussionUpdatedNotification::class);
    }

    public function test_mentionable_users_exclude_the_current_user(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $other = $this->createAdditionalUser($team, [], 'other@example.com');
        $thread = $this->createDiscussionThread($team, $author);

        $mentionables = DiscussionMentionables::forThread($thread, $author);

        $this->assertFalse($mentionables->contains('id', $author->id));
        $this->assertTrue($mentionables->contains('id', $other->id));
    }

    public function test_viewing_thread_clears_unread_discussion_notifications(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $participant = $this->createAdditionalUser($team, [], 'participant@example.com');
        $thread = $this->createDiscussionThread($team, $author);

        $post = DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'body' => 'Another update',
        ]);

        $participant->notify(new DiscussionUpdatedNotification($post));

        $this->assertSame(1, $participant->unreadNotifications()->count());

        Livewire::actingAs($participant)
            ->test(Show::class, ['team' => $team, 'thread' => $thread]);

        $this->assertSame(0, $participant->fresh()->unreadNotifications()->count());
    }

    public function test_discussion_notifications_link_to_the_relevant_post(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $participant = $this->createAdditionalUser($team, [], 'participant@example.com');
        $thread = $this->createDiscussionThread($team, $author);

        $post = DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $author->id,
            'body' => 'Follow-up reply',
        ]);

        $participant->notify(new DiscussionUpdatedNotification($post));

        $notification = $participant->fresh()->notifications->first();
        $expectedUrl = route('teams.discussions.show', [
            'team' => $team->id,
            'thread' => $thread->id,
            'post' => $post->id,
        ]).'#post-'.$post->id;

        $this->assertSame($expectedUrl, $notification->data['url']);
    }

    public function test_post_query_parameter_opens_the_correct_page(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $thread = $this->createDiscussionThread($team, $author);

        $targetPost = null;

        for ($i = 0; $i < 21; $i++) {
            $targetPost = DiscussionPost::query()->create([
                'thread_id' => $thread->id,
                'user_id' => $author->id,
                'body' => 'Reply '.$i,
                'created_at' => now()->addMinutes($i + 1),
            ]);
        }

        $this->assertSame(2, DiscussionPostUrl::pageForPost($thread, $targetPost->id));

        Livewire::withQueryParams(['post' => $targetPost->id])
            ->actingAs($author)
            ->test(Show::class, ['team' => $team, 'thread' => $thread])
            ->assertSee('Reply 20', false)
            ->assertSee('id="post-'.$targetPost->id.'"', false);
    }

    protected function createDiscussionThread(Team $team, User $user, array $overrides = []): DiscussionThread
    {
        $thread = DiscussionThread::query()->create(array_merge([
            'team_id' => $team->id,
            'title' => 'Test Thread',
            'scope' => 'team',
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
