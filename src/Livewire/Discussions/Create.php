<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Events\ThreadCreated;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use App\Models\Team;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    use InteractsWithBanner;

    public Team $team;

    public string $title = '';

    public string $scope = 'team';

    public ?int $propertyId = null;

    public string $body = '';

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('create', [DiscussionThread::class, $team]), 403);

        $this->team = $team;
    }

    public function store(): void
    {
        abort_unless(Auth::user()->can('create', [DiscussionThread::class, $this->team]), 403);

        $propertyModel = config('afterburner-communications.property_model');
        $hasProperties = $propertyModel && class_exists($propertyModel);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(array_column(DiscussionThreadScope::cases(), 'value'))],
            'propertyId' => [
                Rule::requiredIf(fn () => $this->scope === DiscussionThreadScope::Property->value && $hasProperties),
                'nullable',
                'integer',
            ],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        if ($this->scope === DiscussionThreadScope::Property->value && $hasProperties) {
            abort_unless(
                $propertyModel::query()->where('team_id', $this->team->id)->whereKey($this->propertyId)->exists(),
                422
            );
        }

        $thread = DiscussionThread::query()->create([
            'team_id' => $this->team->id,
            'title' => $this->title,
            'scope' => $this->scope,
            'property_id' => $this->scope === DiscussionThreadScope::Property->value ? $this->propertyId : null,
            'created_by' => Auth::id(),
        ]);

        DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
        ]);

        event(new ThreadCreated($thread));

        $this->redirectRoute('teams.discussions.show', [
            'team' => $this->team->id,
            'thread' => $thread->id,
        ], navigate: true);
    }

    /**
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function getPropertiesProperty()
    {
        $propertyModel = config('afterburner-communications.property_model');

        if (! $propertyModel || ! class_exists($propertyModel)) {
            return collect();
        }

        return $propertyModel::query()
            ->where('team_id', $this->team->id)
            ->orderBy('lot_number')
            ->get(['id', 'lot_number']);
    }

    public function render()
    {
        return view('afterburner-communications::discussions.livewire.create');
    }
}
