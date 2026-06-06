<div
    class="space-y-6"
    x-data
    @scroll-to-reply="$nextTick(() => {
        document.getElementById('discussion-reply-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('replyBody')?.focus();
    })"
>
    <div class="relative rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="absolute right-4 top-4 flex items-center gap-1">
                @canany(['update', 'archive', 'lock', 'delete'], $thread)
                    @can('update', $thread)
                        <x-action-icon
                            type="edit"
                            wire:click="editThread"
                            title="{{ __('Edit thread') }}"
                        />
                    @endcan

                    @can('archive', $thread)
                        @if($thread->isArchived())
                            <x-afterburner-communications::thread-action-icon
                                type="unarchive"
                                wire:click="unarchiveThread"
                                title="{{ __('Restore thread') }}"
                            />
                        @else
                            <x-afterburner-communications::thread-action-icon
                                type="archive"
                                wire:click="archiveThread"
                                title="{{ __('Archive thread') }}"
                            />
                        @endif
                    @endcan

                    @can('lock', $thread)
                        @if($thread->isLocked())
                            <x-afterburner-communications::thread-action-icon
                                type="lock"
                                wire:click="unlockThread"
                                title="{{ __('Unlock thread') }}"
                            />
                        @else
                            <x-afterburner-communications::thread-action-icon
                                type="unlock"
                                wire:click="lockThread"
                                title="{{ __('Lock thread') }}"
                            />
                        @endif
                    @endcan

                    @can('delete', $thread)
                        <x-action-icon
                            type="delete"
                            wire:click="confirmThreadDeletion"
                            title="{{ __('Delete thread') }}"
                        />
                    @endcan
                @else
                    @if($thread->isArchived())
                        <x-afterburner-communications::thread-action-icon
                            type="archive"
                            disabled
                            class="pointer-events-none"
                            title="{{ __('Archived') }}"
                        />
                    @endif

                    @if($thread->isLocked())
                        <x-afterburner-communications::thread-action-icon
                            type="lock"
                            disabled
                            class="pointer-events-none"
                            title="{{ __('Locked') }}"
                        />
                    @endif
                @endcanany
        </div>

        <div class="pe-28">
            <p class="text-sm font-medium tracking-wide text-gray-500 dark:text-gray-400">{{ $thread->scope->label() }} Discussion</p>
            <h3 class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ $thread->title }}</h3>
        </div>
    </div>

    <div class="space-y-4">
        @foreach($posts as $post)
            <div
                id="post-{{ $post->id }}"
                class="scroll-mt-24 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-shadow duration-500 dark:border-gray-700 dark:bg-gray-800 [&:target]:border-indigo-400 [&:target]:ring-2 [&:target]:ring-indigo-400/60 dark:[&:target]:border-indigo-500 dark:[&:target]:ring-indigo-500/50"
                wire:key="post-{{ $post->id }}"
            >
                <div class="mb-2 flex flex-wrap items-start justify-between gap-2 text-base text-gray-500 dark:text-gray-400">
                    <div>
                        <span class="ml-1 font-medium text-gray-700 dark:text-gray-300">{{ $post->user->name }}</span>
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

                <div class="rounded-lg border bg-gray-50 p-3 dark:bg-gray-900/50">
                    @if($post->quotedPost)
                        <div class="mb-2 flex flex-col gap-0 border-l-4 border-indigo-400 bg-indigo-50 py-1.5 pl-2 pr-2 text-sm dark:border-indigo-600 dark:bg-indigo-950/40">
                            <p class="font-medium leading-tight text-indigo-900 dark:text-indigo-200">
                                {{ __('Quoting :name', ['name' => $post->quotedPost->user->name]) }}
                            </p>
                            <p class="line-clamp-4 whitespace-pre-line leading-tight italic text-indigo-700 dark:text-indigo-300">
                                &ldquo;{{ trim(Str::limit($post->quotedPost->body, 300)) }}&rdquo;
                            </p>
                        </div>
                    @endif

                    <div class="prose prose-sm dark:prose-invert max-w-none whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-200">{!! \Afterburner\Communications\Support\DiscussionMentionFormatter::format($post->body, $post->mentions) !!}</div>
                </div>
                        
                <div class="text-xs mt-1 mr-2 text-right">
                    {!! format_date_superscript($post->created_at, 'datetime') !!}
                    
                    @if($post->edited_at)
                        <span class="ml-1 text-xs italic">({{ __('edited') }})</span>
                    @endif
                </div>

            </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $posts->links() }}
    </div>

    @can('post', $thread)
        <form id="discussion-reply-form" wire:submit="postReply" class="scroll-mt-24 space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            @if($this->quotedPost)
                <div class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-2 dark:border-indigo-800 dark:bg-indigo-950/30">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-1 flex-col gap-0 text-sm">
                            <p class="font-medium leading-tight text-indigo-900 dark:text-indigo-200">
                                {{ __('Quoting :name', ['name' => $this->quotedPost->user->name]) }}
                            </p>
                            <p class="line-clamp-3 whitespace-pre-line leading-tight italic text-indigo-700 dark:text-indigo-300">
                                &ldquo;{{ trim(Str::limit($this->quotedPost->body, 200)) }}&rdquo;
                            </p>
                        </div>
                        <button type="button" wire:click="cancelQuote" class="shrink-0 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                            <span class="sr-only">{{ __('Remove quote') }}</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
            @endif

            <x-afterburner-communications::mention-textarea
                id="replyBody"
                wire-model="replyBody"
                :mentionable-users="$mentionableUsers"
                :label="__('Reply')"
                rows="4"
            />
            <x-input-error for="replyBody" class="mt-2" />
            <div class="flex items-center justify-end gap-3">
                <x-action-message on="replied" />
                <x-button type="submit" wire:loading.attr="disabled" wire:target="postReply">
                    {{ __('Post reply') }}
                </x-button>
            </div>
        </form>
    @elseif($thread->isArchived())
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is archived.') }}</p>
        </div>
    @elseif($thread->isLocked())
        <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This thread is locked.') }}</p>
        </div>
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
                        <option value="team">{{ entity_title() }} (all members)</option>
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
                <x-afterburner-communications::mention-textarea
                    id="editPostBody"
                    wire-model="editPostBody"
                    :mentionable-users="$mentionableUsers"
                    :label="__('Post')"
                    rows="6"
                />
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
