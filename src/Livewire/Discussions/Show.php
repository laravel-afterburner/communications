<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\PropertySelectOptions;
use App\Models\Team;
use App\Support\ValidationAttributes;
use App\Traits\InteractsWithBanner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    public Team $team;

    public DiscussionThread $thread;

    public string $replyBody = '';

    public ?int $quotedPostId = null;

    public bool $editingThread = false;

    /** @var array{title: string, scope: string, propertyIds: array<int, string>} */
    public array $editThreadForm = [
        'title' => '',
        'scope' => 'team',
        'propertyIds' => [],
    ];

    public bool $confirmingThreadDeletion = false;

    public bool $editingPost = false;

    public ?int $postBeingEdited = null;

    public string $editPostBody = '';

    public bool $confirmingPostDeletion = false;

    public ?int $postBeingDeleted = null;

    public function mount(Team $team, DiscussionThread $thread): void
    {
        abort_unless($team->id === Auth::user()?->currentTeam?->id, 403);
        abort_unless($thread->team_id === $team->id, 404);
        abort_unless(Auth::user()?->can('view', $thread), 403);

        $this->team = $team;
        $this->loadThread();
    }

    public function quotePost(int $postId): void
    {
        abort_unless(Auth::user()->can('post', $this->thread), 403);

        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($postId);

        $this->quotedPostId = $post->id;
    }

    public function cancelQuote(): void
    {
        $this->quotedPostId = null;
    }

    public function postReply(): void
    {
        abort_unless(Auth::user()->can('post', $this->thread), 403);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:10000'],
            'quotedPostId' => ['nullable', 'integer'],
        ]);

        if ($this->quotedPostId !== null) {
            abort_unless(
                DiscussionPost::query()
                    ->where('thread_id', $this->thread->id)
                    ->whereKey($this->quotedPostId)
                    ->exists(),
                422
            );
        }

        DiscussionPost::query()->create([
            'thread_id' => $this->thread->id,
            'user_id' => Auth::id(),
            'body' => $this->replyBody,
            'quoted_post_id' => $this->quotedPostId,
        ]);

        $this->thread->touch();
        $this->replyBody = '';
        $this->quotedPostId = null;
        $this->resetPage('postsPage');
        $this->loadThread();
        $this->dispatch('replied');
        $this->banner(__('Reply posted.'));
    }

    public function editThread(): void
    {
        abort_unless(Auth::user()->can('update', $this->thread), 403);

        $this->editThreadForm = [
            'title' => $this->thread->title,
            'scope' => $this->thread->scope->value,
            'propertyIds' => DiscussionThread::propertyModelClass() !== null
                ? $this->thread->properties
                    ->pluck('id')
                    ->map(fn ($id) => (string) $id)
                    ->all()
                : [],
        ];
        $this->editingThread = true;
    }

    public function updatedEditThreadFormScope(string $scope): void
    {
        if ($scope !== DiscussionThreadScope::Property->value) {
            $this->editThreadForm['propertyIds'] = [];
        }
    }

    public function cancelEditThread(): void
    {
        $this->editingThread = false;
        $this->resetValidation();
    }

    public function updateThread(): void
    {
        abort_unless(Auth::user()->can('update', $this->thread), 403);

        $propertyModel = config('afterburner-communications.property_model');
        $hasProperties = $propertyModel && class_exists($propertyModel);

        $this->validate([
            'editThreadForm.title' => ['required', 'string', 'max:255'],
            'editThreadForm.scope' => ['required', Rule::in(array_column(DiscussionThreadScope::cases(), 'value'))],
            'editThreadForm.propertyIds' => [
                Rule::excludeIf(fn () => $this->editThreadForm['scope'] !== DiscussionThreadScope::Property->value || ! $hasProperties),
                'required',
                'array',
                'min:1',
            ],
            'editThreadForm.propertyIds.*' => [
                Rule::excludeIf(fn () => $this->editThreadForm['scope'] !== DiscussionThreadScope::Property->value || ! $hasProperties),
                'integer',
            ],
        ], [], ValidationAttributes::merge([
            'editThreadForm.title' => 'title',
            'editThreadForm.scope' => 'scope',
            'editThreadForm.propertyIds' => 'properties',
            'editThreadForm.propertyIds.*' => 'lot',
        ]));

        $normalizedPropertyIds = array_values(array_map('intval', $this->editThreadForm['propertyIds']));

        if ($this->editThreadForm['scope'] === DiscussionThreadScope::Property->value && $hasProperties) {
            $validCount = $propertyModel::query()
                ->where('team_id', $this->team->id)
                ->whereIn('id', $normalizedPropertyIds)
                ->count();

            abort_unless($validCount === count($normalizedPropertyIds), 422);
        }

        $this->thread->update([
            'title' => $this->editThreadForm['title'],
            'scope' => $this->editThreadForm['scope'],
        ]);

        if ($hasProperties) {
            if ($this->editThreadForm['scope'] === DiscussionThreadScope::Property->value) {
                $this->thread->properties()->sync($normalizedPropertyIds);
            } else {
                $this->thread->properties()->detach();
            }
        }

        $this->editingThread = false;
        $this->loadThread();
        $this->banner(__('Thread updated.'));
    }

    public function confirmThreadDeletion(): void
    {
        abort_unless(Auth::user()->can('delete', $this->thread), 403);

        $this->confirmingThreadDeletion = true;
    }

    public function cancelThreadDeletion(): void
    {
        $this->confirmingThreadDeletion = false;
    }

    public function deleteThread(): void
    {
        abort_unless(Auth::user()->can('delete', $this->thread), 403);

        $teamId = $this->team->id;
        $this->thread->delete();

        $this->redirectRoute('teams.discussions.index', ['team' => $teamId], navigate: true);
    }

    public function archiveThread(): void
    {
        abort_unless(Auth::user()->can('archive', $this->thread), 403);

        $this->thread->update(['archived_at' => now()]);
        $this->loadThread();
        $this->banner(__('Thread archived.'));
    }

    public function unarchiveThread(): void
    {
        abort_unless(Auth::user()->can('archive', $this->thread), 403);

        $this->thread->update(['archived_at' => null]);
        $this->loadThread();
        $this->banner(__('Thread restored.'));
    }

    public function lockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => now()]);
        $this->loadThread();
        $this->banner(__('Thread locked.'));
    }

    public function unlockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => null]);
        $this->loadThread();
        $this->banner(__('Thread unlocked.'));
    }

    public function editPost(int $postId): void
    {
        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($postId);

        abort_unless(Auth::user()->can('update', $post), 403);

        $this->postBeingEdited = $post->id;
        $this->editPostBody = $post->body;
        $this->editingPost = true;
    }

    public function cancelEditPost(): void
    {
        $this->editingPost = false;
        $this->postBeingEdited = null;
        $this->editPostBody = '';
        $this->resetValidation();
    }

    public function updatePost(): void
    {
        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($this->postBeingEdited);

        abort_unless(Auth::user()->can('update', $post), 403);

        $this->validate([
            'editPostBody' => ['required', 'string', 'max:10000'],
        ]);

        $post->update([
            'body' => $this->editPostBody,
            'edited_at' => now(),
        ]);

        $this->thread->touch();
        $this->cancelEditPost();
        $this->loadThread();
        $this->banner(__('Post updated.'));
    }

    public function confirmPostDeletion(int $postId): void
    {
        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($postId);

        abort_unless(Auth::user()->can('delete', $post), 403);

        $this->postBeingDeleted = $post->id;
        $this->confirmingPostDeletion = true;
    }

    public function cancelPostDeletion(): void
    {
        $this->confirmingPostDeletion = false;
        $this->postBeingDeleted = null;
    }

    public function deletePost(): void
    {
        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($this->postBeingDeleted);

        abort_unless(Auth::user()->can('delete', $post), 403);

        $post->delete();
        $this->thread->touch();
        $this->cancelPostDeletion();
        $this->loadThread();
        $this->banner(__('Post deleted.'));
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

    public function getQuotedPostProperty(): ?DiscussionPost
    {
        if ($this->quotedPostId === null) {
            return null;
        }

        return $this->thread->posts()->with(['user', 'quotedPost.user'])->find($this->quotedPostId)
            ?? DiscussionPost::query()
                ->where('thread_id', $this->thread->id)
                ->with(['user', 'quotedPost.user'])
                ->find($this->quotedPostId);
    }

    protected function loadThread(): void
    {
        $with = [
            'creator',
        ];

        if (DiscussionThread::propertyModelClass() !== null) {
            $with['properties'] = fn ($query) => $query->orderBy('lot_number');
        }

        $this->thread->refresh()->load($with);
    }

    public function render()
    {
        $posts = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->with(['user', 'quotedPost.user'])
            ->orderBy('created_at')
            ->paginate(20, pageName: 'postsPage');

        return view('afterburner-communications::discussions.livewire.show', [
            'posts' => $posts,
            'propertyOptions' => PropertySelectOptions::forSelect(
                $this->properties,
                $this->editThreadForm['propertyIds'],
            ),
        ]);
    }
}
