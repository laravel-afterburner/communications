<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use App\Models\Team;
use App\Traits\InteractsWithBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    public Team $team;

    public string $search = '';

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('viewAny', [DiscussionThread::class, $team]), 403);

        $this->team = $team;
    }

    public function updatedSearch(): void
    {
        $this->resetPage('threadsPage');
        $this->resetPage('postsPage');
    }

    protected function searchTerm(): ?string
    {
        $search = trim($this->search);

        if ($search === '') {
            return null;
        }

        return '%'.addcslashes($search, '%_\\').'%';
    }

    public function render()
    {
        $user = Auth::user();

        $with = [
            'posts' => fn ($q) => $q->with('user')->latest()->limit(1),
        ];

        if (DiscussionThread::propertyModelClass() !== null) {
            $with['properties'] = fn ($q) => $q->orderBy('lot_number');
        }

        $threadsQuery = DiscussionThread::query()
            ->where('team_id', $this->team->id)
            ->visibleTo($user, $this->team->id)
            ->with($with);

        if (($term = $this->searchTerm()) !== null) {
            $threadsQuery->where('title', 'like', $term);
        }

        /** @var LengthAwarePaginator $threads */
        $threads = $threadsQuery
            ->latest('updated_at')
            ->paginate(15, pageName: 'threadsPage');

        $postMatches = null;

        if (($term = $this->searchTerm()) !== null) {
            $postMatches = DiscussionPost::query()
                ->where('body', 'like', $term)
                ->whereHas('thread', function ($query) use ($user) {
                    $query
                        ->where('team_id', $this->team->id)
                        ->visibleTo($user, $this->team->id);
                })
                ->with(['thread', 'user'])
                ->latest()
                ->paginate(10, pageName: 'postsPage');
        }

        return view('afterburner-communications::discussions.livewire.index', [
            'canCreate' => $user?->can('create', [DiscussionThread::class, $this->team]),
            'threads' => $threads,
            'postMatches' => $postMatches,
        ]);
    }
}
