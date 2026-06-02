<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Events\ThreadCreated;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\DiscussionMentionables;
use Afterburner\Communications\Support\DiscussionNotificationService;
use Afterburner\Communications\Support\PropertySelectOptions;
use App\Models\Team;
use App\Support\ValidationAttributes;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    use InteractsWithBanner;

    public Team $team;

    public string $title = '';

    public string $scope = 'team';

    /** @var array<int, string> */
    public array $propertyIds = [];

    public string $body = '';

    public function mount(Team $team): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless(Auth::user()?->can('create', [DiscussionThread::class, $team]), 403);

        $this->team = $team;
    }

    public function updatedScope(): void
    {
        if ($this->scope !== DiscussionThreadScope::Property->value) {
            $this->propertyIds = [];
        }
    }

    public function store(): void
    {
        abort_unless(Auth::user()->can('create', [DiscussionThread::class, $this->team]), 403);

        $propertyModel = config('afterburner-communications.property_model');
        $hasProperties = $propertyModel && class_exists($propertyModel);

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'scope' => ['required', Rule::in(array_column(DiscussionThreadScope::cases(), 'value'))],
            'propertyIds' => [
                Rule::excludeIf(fn () => $this->scope !== DiscussionThreadScope::Property->value || ! $hasProperties),
                'required',
                'array',
                'min:1',
            ],
            'propertyIds.*' => [
                Rule::excludeIf(fn () => $this->scope !== DiscussionThreadScope::Property->value || ! $hasProperties),
                'integer',
            ],
            'body' => ['required', 'string', 'max:10000'],
        ], [], ValidationAttributes::merge([
            'propertyIds' => 'properties',
            'propertyIds.*' => 'lot',
            'body' => 'opening post',
        ]));

        $normalizedPropertyIds = $this->normalizedPropertyIds();

        if ($this->scope === DiscussionThreadScope::Property->value && $hasProperties) {
            $validCount = $propertyModel::query()
                ->where('team_id', $this->team->id)
                ->whereIn('id', $normalizedPropertyIds)
                ->count();

            abort_unless($validCount === count($normalizedPropertyIds), 422);
        }

        $thread = DiscussionThread::query()->create([
            'team_id' => $this->team->id,
            'title' => $this->title,
            'scope' => $this->scope,
            'created_by' => Auth::id(),
        ]);

        if ($this->scope === DiscussionThreadScope::Property->value && $hasProperties) {
            $thread->properties()->sync($normalizedPropertyIds);
        }

        $post = DiscussionPost::query()->create([
            'thread_id' => $thread->id,
            'user_id' => Auth::id(),
            'body' => $this->body,
        ]);

        $notificationService = app(DiscussionNotificationService::class);
        $notificationService->syncMentions($post, $this->body, $thread);
        $notificationService->notifyForPost($post, Auth::user());

        event(new ThreadCreated($thread));

        $this->redirectRoute('teams.discussions.show', [
            'team' => $this->team->id,
            'thread' => $thread->id,
        ], navigate: true);
    }

    /**
     * @return Collection<int, mixed>
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

    /**
     * @return array<int, int>
     */
    protected function normalizedPropertyIds(): array
    {
        return array_values(array_map('intval', $this->propertyIds));
    }

    public function render()
    {
        return view('afterburner-communications::discussions.livewire.create', [
            'propertyOptions' => PropertySelectOptions::forSelect($this->properties, $this->propertyIds),
            'mentionableUsers' => DiscussionMentionables::asSelectOptions(
                DiscussionMentionables::forNewThread(
                    $this->team,
                    DiscussionThreadScope::from($this->scope),
                    Auth::user(),
                ),
            )->all(),
        ]);
    }
}
