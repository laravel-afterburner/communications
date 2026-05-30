<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Models\DiscussionThread;
use App\Models\Team;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    public Team $team;

    public string $scopeFilter = '';

    public string $archiveFilter = 'active';

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('viewAny', [DiscussionThread::class, $team]), 403);

        $this->team = $team;
    }

    public function viewThread(int $threadId)
    {
        return $this->redirectRoute('teams.discussions.show', ['team' => $this->team, 'thread' => $threadId]);
    }

    public function createThread()
    {
        return $this->redirectRoute('teams.discussions.create', ['team' => $this->team]);
    }

    public function getThreadsProperty()
    {
        $user = Auth::user();

        return DiscussionThread::query()
            ->where('team_id', $this->team->id)
            ->visibleTo($user, $this->team->id)
            ->with(['creator', 'posts' => fn ($q) => $q->latest()->limit(1)])
            ->when($this->scopeFilter !== '', fn ($q) => $q->where('scope', $this->scopeFilter))
            ->when($this->archiveFilter === 'active', fn ($q) => $q->notArchived())
            ->when($this->archiveFilter === 'archived', fn ($q) => $q->archived())
            ->latest('updated_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('afterburner-communications::discussions.livewire.index', [
            'canCreate' => Auth::user()?->can('create', [DiscussionThread::class, $this->team]),
        ]);
    }
}
