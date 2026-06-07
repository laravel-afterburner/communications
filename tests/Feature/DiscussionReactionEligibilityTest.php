<?php

namespace Afterburner\Communications\Tests\Feature;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\DiscussionReactionEligibility;
use Afterburner\Communications\Tests\TestCase;
use App\Models\Team;
use App\Models\User;

class DiscussionReactionEligibilityTest extends TestCase
{
    public function test_denial_message_for_own_post(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $post = $this->createDiscussionPost($team, $author);

        $message = DiscussionReactionEligibility::denialMessage($author, $post);

        $this->assertSame('You cannot react to your own post.', $message);
    }

    public function test_denial_message_for_archived_thread(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, ['view_discussions'], 'member@example.com');
        $post = $this->createDiscussionPost($team, $author, ['archived_at' => now()]);

        $message = DiscussionReactionEligibility::denialMessage($member, $post);

        $this->assertSame('Reactions are disabled on archived discussions.', $message);
    }

    public function test_denial_message_is_null_when_user_can_react(): void
    {
        [$author, $team] = $this->createTeamWithUser(['manage_discussions']);
        $member = $this->createAdditionalUser($team, ['view_discussions'], 'member@example.com');
        $post = $this->createDiscussionPost($team, $author);

        $this->assertNull(DiscussionReactionEligibility::denialMessage($member, $post));
    }

    protected function createDiscussionPost(Team $team, User $user, array $threadOverrides = []): DiscussionPost
    {
        $thread = DiscussionThread::query()->create(array_merge([
            'team_id' => $team->id,
            'title' => 'Test Thread',
            'scope' => DiscussionThreadScope::Team,
            'created_by' => $user->id,
        ], $threadOverrides));

        return DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => $user->id,
            'body' => 'Opening post body',
        ])->load('thread');
    }
}
