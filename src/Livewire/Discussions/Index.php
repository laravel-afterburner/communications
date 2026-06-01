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

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('viewAny', [DiscussionThread::class, $team]), 403);

        $this->team = $team;
    }

    public function getThreadsProperty()
    {
        $user = Auth::user();

        $with = [
            'posts' => fn ($q) => $q->with('user')->latest()->limit(1),
        ];

        if (DiscussionThread::propertyModelClass() !== null) {
            $with['properties'] = fn ($q) => $q->orderBy('lot_number');
        }

        return DiscussionThread::query()
            ->where('team_id', $this->team->id)
            ->visibleTo($user, $this->team->id)
            ->with($with)
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
