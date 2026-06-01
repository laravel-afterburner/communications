<div class="space-y-6">
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ $thread->scope->label() }}</p>
                <h3 class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $thread->title }}</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if($thread->isLocked())
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {{ __('Locked') }}
                        </span>
                    @endif
                    @if($thread->isArchived())
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                            {{ __('Archived') }}
                        </span>
                    @endif
                </div>
            </div>

            @can('update', $thread)
                <div class="flex flex-wrap items-center gap-2">
                    <x-secondary-button wire:click="editThread" type="button" no-spinner>{{ __('Edit') }}</x-secondary-button>

                    @if($thread->isArchived())
                        <x-secondary-button wire:click="unarchiveThread" type="button" no-spinner>{{ __('Restore') }}</x-secondary-button>
                    @else
                        <x-secondary-button wire:click="archiveThread" type="button" no-spinner>{{ __('Archive') }}</x-secondary-button>
                    @endif

                    @if($thread->isLocked())
                        <x-secondary-button wire:click="unlockThread" type="button" no-spinner>{{ __('Unlock') }}</x-secondary-button>
                    @else
                        <x-secondary-button wire:click="lockThread" type="button" no-spinner>{{ __('Lock thread') }}</x-secondary-button>
                    @endif

                    <x-danger-button wire:click="confirmThreadDeletion" type="button" no-spinner>{{ __('Delete') }}</x-danger-button>
                </div>
            @else
                <div class="flex flex-wrap items-center gap-2">
                    @if($thread->isLocked())
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                            {{ __('Locked') }}
                        </span>
                    @endif
                </div>
            @endcan
        </div>
    </div>

    <div class="space-y-4">
        @foreach($posts as $post)
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800" wire:key="post-{{ $post->id }}">
                <div class="mb-2 flex flex-wrap items-start justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $post->user->name }}</span>
                        <span class="mx-2">&middot;</span>
                        <span>{!! format_date_superscript($post->created_at, 'datetime') !!}</span>
                        @if($post->edited_at)
                            <span class="ml-1 text-xs italic">({{ __('edited') }})</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-1">
                        @can('post', $thread)
                            <x-secondary-button wire:click="quotePost({{ $post->id }})" type="button" no-spinner class="!px-2 !py-1 !text-xs">
                                {{ __('Quote') }}
                            </x-secondary-button>
                        @endcan
                        @can('update', $post)
                            <x-action-icon type="edit" wire:click="editPost({{ $post->id }})" title="{{ __('Edit post') }}" />
                        @endcan
                        @can('delete', $post)
                            <x-action-icon type="delete" wire:click="confirmPostDeletion({{ $post->id }})" title="{{ __('Delete post') }}" />
                        @endcan
                    </div>
                </div>

                @if($post->quotedPost)
                    <div class="mb-3 border-l-4 border-indigo-300 bg-gray-50 px-3 py-2 text-sm dark:border-indigo-600 dark:bg-gray-900/50">
                        <p class="font-medium text-gray-600 dark:text-gray-400">
                            {{ __('Quoting :name', ['name' => $post->quotedPost->user->name]) }}
                        </p>
                        <div class="mt-1 line-clamp-4 whitespace-pre-wrap text-gray-500 dark:text-gray-400">
                            {{ Str::limit($post->quotedPost->body, 300) }}
                        </div>
                    </div>
                @endif

                <div class="prose dark:prose-invert max-w-none whitespace-pre-wrap text-gray-800 dark:text-gray-200">{{ $post->body }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $posts->links() }}
    </div>

    @can('post', $thread)
        <form wire:submit="postReply" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @if($this->quotedPost)
                <div class="rounded-md border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-800 dark:bg-indigo-950/30">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="font-medium text-indigo-900 dark:text-indigo-200">
                                {{ __('Quoting :name', ['name' => $this->quotedPost->user->name]) }}
                            </p>
                            <div class="mt-1 line-clamp-3 whitespace-pre-wrap text-indigo-700 dark:text-indigo-300">
                                {{ Str::limit($this->quotedPost->body, 200) }}
                            </div>
                        </div>
                        <button type="button" wire:click="cancelQuote" class="shrink-0 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                            <span class="sr-only">{{ __('Remove quote') }}</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            @endif

            <div>
                <x-label for="replyBody" value="{{ __('Reply') }}" />
                <x-textarea-input id="replyBody" wire:model="replyBody" rows="4" class="mt-1 block w-full" />
                <x-input-error for="replyBody" class="mt-2" />
            </div>
            <div class="flex items-center justify-end gap-3">
                <x-action-message on="replied" />
                <x-button type="submit" wire:loading.attr="disabled" wire:target="postReply">
                    {{ __('Post reply') }}
                </x-button>
            </div>
        </form>
    @elseif($thread->isArchived())
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is archived.') }}</p>
    @elseif($thread->isLocked())
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is locked.') }}</p>
    @endif

    <x-dialog-modal wire:model.live="editingThread">
        <x-slot name="title">
            {{ __('Edit thread') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="edit_thread_title" value="{{ __('Title') }}" />
                    <x-input id="edit_thread_title" type="text" class="mt-1 block w-full" wire:model="editThreadForm.title" />
                    <x-input-error for="editThreadForm.title" class="mt-2" />
                </div>

                <div>
                    <x-label for="edit_thread_scope" value="{{ __('Scope') }}" />
                    <x-select-input id="edit_thread_scope" wire:model.live="editThreadForm.scope" class="mt-1 block w-full">
                        <option value="team">{{ __('Team (all members)') }}</option>
                        <option value="council">{{ __('Council only') }}</option>
                        @if($this->properties->isNotEmpty())
                            <option value="property">{{ __('Property') }}</option>
                        @endif
                    </x-select-input>
                    <x-input-error for="editThreadForm.scope" class="mt-2" />
                </div>

                @if($editThreadForm['scope'] === 'property' && $this->properties->isNotEmpty())
                    <div class="overflow-visible">
                        <x-label for="edit_thread_property" value="{{ __('Properties') }}" />
                        <x-afterburner-communications::property-select
                            id="edit_thread_property"
                            wire-model="editThreadForm.propertyIds"
                            :options="$propertyOptions"
                            :selected="$editThreadForm['propertyIds']"
                        />
                        <x-input-error for="editThreadForm.propertyIds" class="mt-2" />
                        <x-input-error for="editThreadForm.propertyIds.*" class="mt-2" />
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelEditThread" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="updateThread" wire:loading.attr="disabled">
                {{ __('Update') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.live="confirmingThreadDeletion">
        <x-slot name="title">
            {{ __('Delete thread') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete this thread? All posts will be permanently removed.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelThreadDeletion" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="deleteThread" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <x-dialog-modal wire:model.live="editingPost">
        <x-slot name="title">
            {{ __('Edit post') }}
        </x-slot>

        <x-slot name="content">
            <div>
                <x-label for="editPostBody" value="{{ __('Post') }}" />
                <x-textarea-input id="editPostBody" wire:model="editPostBody" rows="6" class="mt-1 block w-full" />
                <x-input-error for="editPostBody" class="mt-2" />
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelEditPost" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="updatePost" wire:loading.attr="disabled">
                {{ __('Update') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <x-confirmation-modal wire:model.live="confirmingPostDeletion">
        <x-slot name="title">
            {{ __('Delete post') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete this post? This action cannot be undone.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelPostDeletion" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="deletePost" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
