<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use App\Models\Team;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    use InteractsWithBanner;

    public Team $team;

    public DiscussionThread $thread;

    public string $replyBody = '';

    public function mount(Team $team, DiscussionThread $thread): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless($thread->team_id === $team->id, 404);
        abort_unless(Auth::user()?->can('view', $thread), 403);

        $this->team = $team;
        $this->thread = $thread->load(['creator', 'posts.user']);
    }

    public function postReply(): void
    {
        abort_unless(Auth::user()->can('post', $this->thread), 403);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:10000'],
        ]);

        DiscussionPost::query()->create([
            'thread_id' => $this->thread->id,
            'user_id' => Auth::id(),
            'body' => $this->replyBody,
        ]);

        $this->thread->touch();
        $this->replyBody = '';
        $this->thread->load(['posts.user']);
        $this->dispatch('replied');
        $this->banner(__('Reply posted.'));
    }

    public function lockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => now()]);
        $this->banner(__('Thread locked.'));
    }

    public function unlockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => null]);
        $this->banner(__('Thread unlocked.'));
    }

    public function render()
    {
        return view('afterburner-communications::discussions.livewire.show');
    }
}
