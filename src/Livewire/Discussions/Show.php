<?php

namespace Afterburner\Communications\Livewire\Discussions;

use Afterburner\Communications\Enums\DiscussionPostReactionType;
use Afterburner\Communications\Enums\DiscussionThreadScope;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionPostReaction;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Support\CommunicationsAuditLogger;
use Afterburner\Communications\Support\DiscussionMentionables;
use Afterburner\Communications\Support\DiscussionNotificationService;
use Afterburner\Communications\Support\DiscussionPostUrl;
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
        $this->focusPostFromRequest();

        app(DiscussionNotificationService::class)->clearForUserAndThread(Auth::user(), $this->thread);
        $this->dispatch('refresh-notifications');
    }

    public function quotePost(int $postId): void
    {
        abort_unless(Auth::user()->can('post', $this->thread), 403);

        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->findOrFail($postId);

        $this->quotedPostId = $post->id;

        $this->dispatch('scroll-to-reply');
    }

    public function cancelQuote(): void
    {
        $this->quotedPostId = null;
    }

    public function toggleReaction(int $postId, string $type): void
    {
        $reactionType = DiscussionPostReactionType::from($type);

        $post = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->with('thread')
            ->findOrFail($postId);

        abort_unless(Auth::user()->can('react', $post), 403);

        $existing = DiscussionPostReaction::query()
            ->where('discussion_post_id', $post->id)
            ->where('user_id', Auth::id())
            ->first();

        $actor = Auth::user();

        if ($existing === null) {
            DiscussionPostReaction::query()->create([
                'discussion_post_id' => $post->id,
                'user_id' => $actor->id,
                'type' => $reactionType,
            ]);

            CommunicationsAuditLogger::postReactionAdded($post, $this->thread, $actor, $reactionType);
        } elseif ($existing->type === $reactionType) {
            $existing->delete();

            CommunicationsAuditLogger::postReactionRemoved($post, $this->thread, $actor, $reactionType);
        } else {
            $previousType = $existing->type;
            $existing->update(['type' => $reactionType]);

            CommunicationsAuditLogger::postReactionChanged($post, $this->thread, $actor, $previousType, $reactionType);
        }
    }

    public function postReply(): void
    {
        abort_unless(Auth::user()->can('post', $this->thread), 403);

        $this->validate([
            'replyBody' => ['required', 'string', 'max:10000'],
            'quotedPostId' => ['nullable', 'integer'],
        ]);

        $quotedPost = null;

        if ($this->quotedPostId !== null) {
            $quotedPost = DiscussionPost::query()
                ->where('thread_id', $this->thread->id)
                ->with('user')
                ->find($this->quotedPostId);

            abort_unless($quotedPost !== null, 422);
        }

        $post = DiscussionPost::query()->create([
            'thread_id' => $this->thread->id,
            'user_id' => Auth::id(),
            'body' => $this->replyBody,
            'quoted_post_id' => $quotedPost?->id,
            'quoted_post_body' => $quotedPost?->body,
            'quoted_post_author_name' => $quotedPost?->user->name,
            'quoted_post_created_at' => $quotedPost?->created_at,
        ]);

        $notificationService = app(DiscussionNotificationService::class);
        $notificationService->syncMentions($post, $this->replyBody, $this->thread);
        $notificationService->notifyForPost($post, Auth::user());

        CommunicationsAuditLogger::postCreated($post, $this->thread, Auth::user());

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

        $beforeTitle = $this->thread->title;
        $beforeScope = $this->thread->scope->value;

        $this->thread->update([
            'title' => $this->editThreadForm['title'],
            'scope' => $this->editThreadForm['scope'],
        ]);

        $fieldChanges = [];

        if ($beforeTitle !== $this->thread->title) {
            $fieldChanges['title'] = ['before' => $beforeTitle, 'after' => $this->thread->title];
        }

        if ($beforeScope !== $this->thread->scope->value) {
            $fieldChanges['scope'] = ['before' => $beforeScope, 'after' => $this->thread->scope->value];
        }

        if ($fieldChanges !== []) {
            CommunicationsAuditLogger::threadUpdated($this->thread, Auth::user(), $fieldChanges);
        }

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

        $openingPostExcerpt = DiscussionPost::query()
            ->where('thread_id', $this->thread->id)
            ->orderBy('id')
            ->value('body');

        CommunicationsAuditLogger::threadDeleted($this->thread, Auth::user(), $openingPostExcerpt);

        $teamId = $this->team->id;
        $this->thread->delete();

        $this->redirectRoute('teams.discussions.index', ['team' => $teamId], navigate: true);
    }

    public function archiveThread(): void
    {
        abort_unless(Auth::user()->can('archive', $this->thread), 403);

        $this->thread->update(['archived_at' => now()]);
        CommunicationsAuditLogger::threadArchived($this->thread, Auth::user(), archived: true);
        $this->loadThread();
        $this->banner(__('Thread archived.'));
    }

    public function unarchiveThread(): void
    {
        abort_unless(Auth::user()->can('archive', $this->thread), 403);

        $this->thread->update(['archived_at' => null]);
        CommunicationsAuditLogger::threadArchived($this->thread, Auth::user(), archived: false);
        $this->loadThread();
        $this->banner(__('Thread restored.'));
    }

    public function lockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => now()]);
        CommunicationsAuditLogger::threadLocked($this->thread, Auth::user(), locked: true);
        $this->loadThread();
        $this->banner(__('Thread locked.'));
    }

    public function unlockThread(): void
    {
        abort_unless(Auth::user()->can('lock', $this->thread), 403);

        $this->thread->update(['locked_at' => null]);
        CommunicationsAuditLogger::threadLocked($this->thread, Auth::user(), locked: false);
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

        $beforeBody = $post->body;

        $post->update([
            'body' => $this->editPostBody,
            'edited_at' => now(),
        ]);

        if ($beforeBody !== $post->body) {
            CommunicationsAuditLogger::postUpdated($post, $this->thread, Auth::user(), [
                'body' => ['before' => $beforeBody, 'after' => $post->body],
            ]);

            $notificationService = app(DiscussionNotificationService::class);
            $notificationService->syncMentions($post, $post->body, $this->thread);
            $notificationService->notifyForPost($post, Auth::user());
        }

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

        CommunicationsAuditLogger::postDeleted($post, $this->thread, Auth::user());

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

        return $this->thread->posts()->with(['user', 'quotedPost.user', 'quotedPost.mentions'])->find($this->quotedPostId)
            ?? DiscussionPost::query()
                ->where('thread_id', $this->thread->id)
                ->with(['user', 'quotedPost.user', 'quotedPost.mentions'])
                ->find($this->quotedPostId);
    }

    protected function focusPostFromRequest(): void
    {
        $postId = request()->integer('post');

        if ($postId <= 0) {
            return;
        }

        $page = DiscussionPostUrl::pageForPost($this->thread, $postId);

        if ($page !== null) {
            $this->setPage($page, 'postsPage');
        }
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
            ->with(['user', 'quotedPost.user', 'quotedPost.mentions', 'mentions', 'reactions.user'])
            ->orderBy('created_at')
            ->paginate(10, pageName: 'postsPage');

        return view('afterburner-communications::discussions.livewire.show', [
            'posts' => $posts,
            'propertyOptions' => PropertySelectOptions::forSelect(
                $this->properties,
                $this->editThreadForm['propertyIds'],
            ),
            'mentionableUsers' => DiscussionMentionables::asSelectOptions(
                DiscussionMentionables::forThread($this->thread, Auth::user()),
            )->all(),
        ]);
    }
}
