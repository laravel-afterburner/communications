<?php

namespace Afterburner\Communications\Livewire\Communications;

use Afterburner\Communications\Enums\CommunicationChannel;
use Afterburner\Communications\Models\CommunicationLogEntry;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LogIndex extends Component
{
    use WithPagination;

    public Team $team;

    public string $channelFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $search = '';

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('viewCommunicationLog', $team), 403);

        $this->team = $team;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function getEntriesProperty()
    {
        return CommunicationLogEntry::query()
            ->where('team_id', $this->team->id)
            ->with('sender')
            ->when($this->channelFilter !== '', function ($q) {
                $q->where('channel', $this->channelFilter);
            })
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('subject', 'like', $term)
                        ->orWhere('body_snapshot', 'like', $term)
                        ->orWhere('recipient_summary', 'like', $term);
                });
            })
            ->latest('created_at')
            ->paginate(20);
    }

    public function render()
    {
        return view('afterburner-communications::communications.livewire.log-index', [
            'channels' => CommunicationChannel::cases(),
        ]);
    }
}
