<div>
    <!-- Announcements List Section -->
    @if($this->announcements->count() > 0)
        <div class="mt-10 sm:mt-0">
    <x-action-section>
        <x-slot name="title">
                    @if($this->canCreateAnnouncements())
                        Announcements
                    @else
                        @if($this->unreadCount > 0)
                            You have {{ $this->unreadCount }} unread {{ Str::plural('announcement', $this->unreadCount) }}
                        @else
                            All announcements
                        @endif
                    @endif
        </x-slot>

        <x-slot name="description">
                    @if($this->canCreateAnnouncements())
                        Manage and view all announcements for your {{ config('afterburner.entity_label') }}.
                    @else
                        View announcements from your {{ config('afterburner.entity_label') }}.
                    @endif
        </x-slot>

        <x-slot name="content">
                    @if($this->canCreateAnnouncements())
                        <div class="mb-6 flex justify-end">
                            <x-button wire:click="openCreateAnnouncementModal" no-spinner>
                                Create announcement
                            </x-button>
                        </div>
                    @elseif($this->unreadCount > 0)
            <div class="flex justify-end mb-4">
                            <button 
                                wire:click="markAllAsRead" 
                                wire:loading.attr="disabled"
                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                                Mark all as read
                            </button>
            </div>
                    @endif

            <div class="space-y-4">
                @foreach($this->announcements as $announcement)
                            @php
                                $isCreator = $announcement->created_by === Auth::id();
                                $isUnread = !$this->canCreateAnnouncements() && $this->isUnread($announcement);
                            @endphp

                            <div class="border rounded-lg p-4 transition-colors
                                {{ $isUnread ? 'border-indigo-200 bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/50' }}">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-2">
                                            @if($this->canCreateAnnouncements())
                                                @if($announcement->isPublished())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                        Published
                                                    </span>
                                                @elseif($announcement->isScheduled())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Scheduled
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                        Draft
                                                    </span>
                                                @endif
                                            @endif
                                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $announcement->title }}
                                            </h4>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap mb-3">{{ trim($announcement->message) }}</div>
                                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                            @if($isCreator)
                                                {{-- Creator sees: Created date, Published date (only if published), Scheduled date (if scheduled), Target roles, Email status, Read count --}}
                                                @php
                                                    $createdTime = $this->team->toTeamTimezone($announcement->created_at);
                                                @endphp
                                                <span>Created: {!! format_date_superscript($createdTime, 'datetime') !!} ({{ $createdTime->format('T') }})</span>
                                                @if($announcement->isPublished())
                                                    @php
                                                        $publishedTime = $this->team->toTeamTimezone($announcement->published_at);
                                                    @endphp
                                                    <span>Published: {!! format_date_superscript($publishedTime, 'datetime') !!} ({{ $publishedTime->format('T') }})</span>
                                                @elseif($announcement->isScheduled())
                                                    @php
                                                        $scheduledTime = $this->team->toTeamTimezone($announcement->published_at);
                                                    @endphp
                                                    <span>Scheduled: {!! format_date_superscript($scheduledTime, 'datetime') !!} ({{ $scheduledTime->format('T') }})</span>
                                                @endif
                                                @if($announcement->isPublished())
                                                    <span class="inline-flex items-center gap-1">
                                                        <span>Read: {{ $announcement->getReadCount() }}/{{ $announcement->getEligibleUsersCount() }}</span>
                                                        <x-afterburner-communications::info-hint
                                                            label="View read status details"
                                                            width="w-72"
                                                            scrollable
                                                        >
                                                            @php
                                                                $readers = $announcement->getReaders();
                                                                $nonReaders = $announcement->getNonReaders();
                                                            @endphp
                                                            <div class="space-y-3">
                                                                @if($readers->count() > 0)
                                                                    <div>
                                                                        <div class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                                            Read ({{ $readers->count() }}):
                                                                        </div>
                                                                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                                                            @foreach($readers as $reader)
                                                                                <div>{{ $reader->name }}</div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                                @if($nonReaders->count() > 0)
                                                                    <div>
                                                                        <div class="mb-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                                                            Not Read ({{ $nonReaders->count() }}):
                                                                        </div>
                                                                        <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                                                                            @foreach($nonReaders as $nonReader)
                                                                                <div>{{ $nonReader->name }}</div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                                @if($readers->count() === 0 && $nonReaders->count() === 0)
                                                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                                                        No eligible users found.
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </x-afterburner-communications::info-hint>
                                                    </span>
                                                @endif
                                                @if($announcement->target_roles)
                                                    <span>Target Roles: {{ implode(', ', $announcement->target_roles) }}</span>
                                                @endif
                                                @if($announcement->send_email)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                        Email
                                                    </span>
                                                @endif
                                            @else
                                                {{-- Non-creator sees: From, Published date (only if published), Target roles (same as view-announcements page) --}}
                                                <span>From: {{ $announcement->creator->name ?? 'System' }}</span>
                                                @if($announcement->isPublished())
                                                    @php
                                                        $publishedTime = $this->team->toTeamTimezone($announcement->published_at);
                                                    @endphp
                                                    <span>Published: {!! format_date_superscript($publishedTime, 'datetime') !!} ({{ $publishedTime->format('T') }})</span>
                                                @endif
                                                @if($announcement->target_roles)
                                                    <span>Target Roles: {{ implode(', ', $announcement->target_roles) }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start space-x-2 flex-shrink-0">
                                        @if($isCreator)
                                            {{-- Creator sees edit and delete buttons --}}
                                            <x-action-icon type="edit" wire:click="editAnnouncement({{ $announcement->id }})" wire:loading.attr="disabled" title="Edit announcement" />
                                            <x-action-icon type="delete" wire:click="confirmAnnouncementDeletion({{ $announcement->id }})" wire:loading.attr="disabled" title="Delete announcement" />
                                        @elseif($isUnread)
                                            {{-- Non-creator sees mark as read button if unread --}}
                                            <button 
                                                wire:click="markAsRead({{ $announcement->id }})" 
                                                wire:loading.attr="disabled"
                                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 dark:border-gray-600 text-xs font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                Mark as read
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                @endforeach
                    </div>

                <!-- Pagination -->
                <div class="mt-4">
                    {{ $this->announcements->links() }}
            </div>
        </x-slot>
    </x-action-section>
        </div>
    @else
        {{-- Empty state --}}
        <div class="mt-10 sm:mt-0">
            <x-action-section>
        <x-slot name="title">
                    @if($this->canCreateAnnouncements())
                        Announcements
                    @else
                        @if($this->unreadCount > 0)
                            You have {{ $this->unreadCount }} unread {{ Str::plural('announcement', $this->unreadCount) }}
                        @else
                            All announcements
                        @endif
                    @endif
                </x-slot>

                <x-slot name="description">
                    @if($this->canCreateAnnouncements())
                        Manage and view all announcements for your {{ config('afterburner.entity_label') }}.
                    @else
                        View announcements from your {{ config('afterburner.entity_label') }}.
                    @endif
        </x-slot>

        <x-slot name="content">
                    @if($this->canCreateAnnouncements())
                        <div class="mb-6 flex justify-end">
                            <x-button wire:click="openCreateAnnouncementModal" no-spinner>
                                Create announcement
                            </x-button>
                        </div>
                    @endif

                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No announcements</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">There are no announcements from your {{ config('afterburner.entity_label') }} at this time.</p>
                    </div>
                </x-slot>
            </x-action-section>
                </div>
    @endif

    <!-- Create Announcement Modal -->
    <x-dialog-modal wire:model.live="creatingAnnouncement" maxWidth="2xl">
        <x-slot name="title">
            {{ __('Create Announcement') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <x-label for="create_title" value="{{ __('Title') }}" />
                    <x-input id="create_title" type="text" class="mt-1 block w-full" wire:model="createAnnouncementForm.title" />
                    <x-input-error for="createAnnouncementForm.title" class="mt-2" />
                </div>

                <!-- Message -->
                <div>
                    <x-label for="create_message" value="{{ __('Message') }}" />
                    <x-textarea-input id="create_message" class="mt-1 block w-full" wire:model="createAnnouncementForm.message" rows="6" />
                    <x-input-error for="createAnnouncementForm.message" class="mt-2" />
                </div>

                <!-- Published At and Send Email -->
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <x-label for="create_published_at" value="Publish Date & Time (Optional)" />
                                <x-afterburner-communications::info-hint
                                    label="Information about publish date"
                                    width="w-72"
                                >
                                    @include('afterburner-communications::announcements.partials.publish-date-hint-content', ['team' => $this->team])
                                </x-afterburner-communications::info-hint>
                            </div>
                            <x-input id="create_published_at" type="datetime-local" class="mt-1 block w-full" wire:model="createAnnouncementForm.published_at" />
                            <x-input-error for="createAnnouncementForm.published_at" class="mt-2" />
                        </div>

                        <div class="flex items-end">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="createAnnouncementForm.send_email" id="create_send_email" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900">
                                <label for="create_send_email" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Send Email Notification') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Roles -->
                @if(count($this->roles) > 0)
                    <div>
                        <div class="flex items-center gap-2">
                            <x-label for="create_target_roles" value="{{ __('Target Roles (Optional)') }}" />
                            <x-afterburner-communications::info-hint
                                label="Information about target roles"
                                text="Leave empty to target all users. Select specific roles to limit visibility."
                                width="w-56"
                            />
                        </div>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-md p-3">
                            @foreach($this->roles as $role)
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model="createAnnouncementForm.target_roles" value="{{ $role->slug }}" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <x-input-error for="createAnnouncementForm.target_roles" class="mt-2" />
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelCreateAnnouncement" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="storeAnnouncement" wire:loading.attr="disabled">
                {{ __('Create') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Edit Announcement Modal -->
    <x-dialog-modal wire:model.live="editingAnnouncement">
        <x-slot name="title">
            {{ __('Edit Announcement') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Title -->
                <div>
                    <x-label for="edit_title" value="{{ __('Title') }}" />
                    <x-input id="edit_title" type="text" class="mt-1 block w-full" wire:model="editAnnouncementForm.title" />
                    <x-input-error for="editAnnouncementForm.title" class="mt-2" />
                </div>

                <!-- Message -->
                <div>
                    <x-label for="edit_message" value="{{ __('Message') }}" />
                    <x-textarea-input id="edit_message" class="mt-1 block w-full" wire:model="editAnnouncementForm.message" rows="6" />
                    <x-input-error for="editAnnouncementForm.message" class="mt-2" />
                </div>

                <!-- Published At and Send Email - Compact Row -->
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Published At -->
                        <div>
                            <div class="flex items-center gap-2">
                    <x-label for="edit_published_at" value="Publish Date & Time (Optional)" />
                                <x-afterburner-communications::info-hint
                                    label="Information about publish date"
                                    width="w-72"
                                >
                                    @include('afterburner-communications::announcements.partials.publish-date-hint-content', ['team' => $this->team])
                                </x-afterburner-communications::info-hint>
                            </div>
                    <x-input id="edit_published_at" type="datetime-local" class="mt-1 block w-full" wire:model="editAnnouncementForm.published_at" />
                    <x-input-error for="editAnnouncementForm.published_at" class="mt-2" />
                        </div>

                        <!-- Send Email -->
                        <div class="flex items-end">
                            <div class="flex items-center">
                                <input type="checkbox" wire:model="editAnnouncementForm.send_email" id="edit_send_email" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900">
                                <label for="edit_send_email" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ __('Send Email Notification') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Target Roles -->
                <div>
                    <div class="flex items-center gap-2">
                    <x-label for="edit_target_roles" value="{{ __('Target Roles (Optional)') }}" />
                        <x-afterburner-communications::info-hint
                            label="Information about target roles"
                            text="Leave empty to target all users. Select specific roles to limit visibility."
                            width="w-56"
                        />
                    </div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-700 rounded-md p-3">
                        @foreach($this->roles as $role)
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="editAnnouncementForm.target_roles" value="{{ $role->slug }}" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="editAnnouncementForm.target_roles" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelEditAnnouncement" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-button class="ms-3" wire:click="updateAnnouncement" wire:loading.attr="disabled">
                {{ __('Update') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Delete Confirmation Modal -->
    <x-confirmation-modal wire:model.live="confirmingAnnouncementDeletion">
        <x-slot name="title">
            {{ __('Delete Announcement') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you would like to delete this announcement? This action cannot be undone.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelAnnouncementDeletion" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3" wire:click="deleteAnnouncement" wire:loading.attr="disabled">
                {{ __('Delete') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>
</div>
