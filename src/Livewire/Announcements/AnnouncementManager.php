<?php

namespace Afterburner\Communications\Livewire\Announcements;

use Afterburner\Communications\Events\AnnouncementPublished;
use Afterburner\Communications\Mail\TeamAnnouncementMail;
use Afterburner\Communications\Models\TeamAnnouncement;
use Afterburner\Communications\Support\CommunicationsAuditLogger;
use Afterburner\Communications\Support\SubscriptionEntitlementGate;
use Afterburner\Support\EntityLabel;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Traits\InteractsWithBanner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AnnouncementManager extends Component
{
    use InteractsWithBanner;
    use WithPagination;

    /**
     * The team instance.
     *
     * @var mixed
     */
    public $team;

    /**
     * Indicates if the application is creating an announcement.
     *
     * @var bool
     */
    public $creatingAnnouncement = false;

    /**
     * Indicates if the application is editing an announcement.
     *
     * @var bool
     */
    public $editingAnnouncement = false;

    /**
     * The announcement being edited.
     *
     * @var mixed
     */
    public $announcementBeingEdited = null;

    /**
     * Indicates if the application is confirming announcement deletion.
     *
     * @var bool
     */
    public $confirmingAnnouncementDeletion = false;

    /**
     * The announcement being deleted.
     *
     * @var mixed
     */
    public $announcementBeingDeleted = null;

    /**
     * The "create announcement" form state.
     *
     * @var array
     */
    public $createAnnouncementForm = [
        'title' => '',
        'message' => '',
        'send_email' => false,
        'published_at' => null,
        'target_roles' => [],
    ];

    /**
     * The "edit announcement" form state.
     *
     * @var array
     */
    public $editAnnouncementForm = [
        'title' => '',
        'message' => '',
        'send_email' => false,
        'published_at' => '',
        'target_roles' => [],
    ];

    /**
     * Mount the component.
     *
     * @param  mixed  $team
     * @return void
     */
    public function mount($team)
    {
        // Handle both model instances and ID strings
        if (is_string($team) || is_numeric($team)) {
            $this->team = Team::findOrFail($team);
        } else {
            $this->team = $team;
        }

        // Ensure user is a member of this team
        if (! Auth::user()->teams->contains($this->team)) {
            abort(403, 'You are not a member of this '.EntityLabel::singular().'.');
        }

        // Ensure this is the user's current team
        if (Auth::user()->currentTeam->id !== $this->team->id) {
            abort(403, 'You can only view announcements for your current '.EntityLabel::singular().'.');
        }

        abort_unless(SubscriptionEntitlementGate::allows($this->team), 403);

        $this->resetCreateAnnouncementForm();
    }

    /**
     * Open the create announcement modal.
     *
     * @return void
     */
    public function openCreateAnnouncementModal()
    {
        if (! $this->canCreateAnnouncements()) {
            return;
        }

        $this->resetErrorBag();
        $this->resetCreateAnnouncementForm();
        $this->creatingAnnouncement = true;
    }

    /**
     * Cancel announcement creation.
     *
     * @return void
     */
    public function cancelCreateAnnouncement()
    {
        $this->resetErrorBag();
        $this->resetCreateAnnouncementForm();
        $this->creatingAnnouncement = false;
    }

    /**
     * Store a new announcement.
     *
     * @return void
     */
    public function storeAnnouncement()
    {
        $this->resetErrorBag();

        if (! SubscriptionEntitlementGate::allows($this->team) || ! Gate::check('postAnnouncements', $this->team)) {
            return;
        }

        $this->validate([
            'createAnnouncementForm.title' => 'required|string|max:255',
            'createAnnouncementForm.message' => 'required|string',
            'createAnnouncementForm.send_email' => 'boolean',
            'createAnnouncementForm.published_at' => 'nullable|date',
            'createAnnouncementForm.target_roles' => 'nullable|array',
            'createAnnouncementForm.target_roles.*' => 'exists:roles,slug',
        ], [
            'createAnnouncementForm.title.required' => 'The title field is required.',
            'createAnnouncementForm.message.required' => 'The message field is required.',
            'createAnnouncementForm.published_at.date' => 'The published date must be a valid date.',
            'createAnnouncementForm.target_roles.*.exists' => 'One or more selected roles are invalid.',
        ]);

        // Get user's timezone for datetime-local conversion
        $userTimezone = Auth::user()->timezone ?? request()->cookie('timezone') ?? null;

        $announcement = TeamAnnouncement::create([
            'team_id' => $this->team->id,
            'title' => $this->createAnnouncementForm['title'],
            'message' => $this->createAnnouncementForm['message'],
            'send_email' => $this->createAnnouncementForm['send_email'],
            'published_at' => $this->createAnnouncementForm['published_at'] ?
                $this->team->fromDateTimeLocal($this->createAnnouncementForm['published_at'], $userTimezone) : null,
            'target_roles' => ! empty($this->createAnnouncementForm['target_roles']) ?
                $this->createAnnouncementForm['target_roles'] : null,
            'created_by' => Auth::id(),
        ]);

        CommunicationsAuditLogger::announcementCreated($announcement, Auth::user());

        // Mark announcement as read for the creator since they already know about it
        $announcement->markAsReadBy(Auth::user());

        if ($announcement->isPublished()) {
            event(new AnnouncementPublished($announcement));
        }

        // Send emails if requested and announcement is published
        if ($announcement->send_email && $announcement->isPublished()) {
            $this->sendAnnouncementEmails($announcement);
        }

        $this->resetCreateAnnouncementForm();
        $this->creatingAnnouncement = false;

        $this->banner(__('Announcement created successfully.'));

        // Refresh navigation menu to update announcement badge
        $this->dispatch('refresh-navigation-menu');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Edit an announcement.
     *
     * @param  int  $announcementId
     * @return void
     */
    public function editAnnouncement($announcementId)
    {
        if (! SubscriptionEntitlementGate::allows($this->team)) {
            return;
        }

        $announcement = TeamAnnouncement::where('team_id', $this->team->id)
            ->findOrFail($announcementId);

        if ($announcement->created_by !== Auth::id() && ! Gate::check('postAnnouncements', $this->team)) {
            return;
        }

        $this->announcementBeingEdited = $announcement;

        // Get user's timezone for datetime-local conversion
        $userTimezone = Auth::user()->timezone ?? request()->cookie('timezone') ?? null;

        $this->editAnnouncementForm = [
            'title' => $announcement->title,
            'message' => $announcement->message,
            'send_email' => $announcement->send_email,
            'published_at' => $announcement->published_at ?
                $this->team->toDateTimeLocal($announcement->published_at, $userTimezone) : '',
            'target_roles' => $announcement->target_roles ?? [],
        ];

        $this->editingAnnouncement = true;
    }

    /**
     * Update the announcement being edited.
     *
     * @return void
     */
    public function updateAnnouncement()
    {
        $this->resetErrorBag();

        if (! SubscriptionEntitlementGate::allows($this->team)) {
            return;
        }

        if ($this->announcementBeingEdited->created_by !== Auth::id() && ! Gate::check('postAnnouncements', $this->team)) {
            return;
        }

        $this->validate([
            'editAnnouncementForm.title' => 'required|string|max:255',
            'editAnnouncementForm.message' => 'required|string',
            'editAnnouncementForm.send_email' => 'boolean',
            'editAnnouncementForm.published_at' => 'nullable|date',
            'editAnnouncementForm.target_roles' => 'nullable|array',
            'editAnnouncementForm.target_roles.*' => 'exists:roles,slug',
        ], [
            'editAnnouncementForm.title.required' => 'The title field is required.',
            'editAnnouncementForm.message.required' => 'The message field is required.',
            'editAnnouncementForm.published_at.date' => 'The published date must be a valid date.',
            'editAnnouncementForm.target_roles.*.exists' => 'One or more selected roles are invalid.',
        ]);

        $wasPublished = $this->announcementBeingEdited->isPublished();
        $beforeTitle = $this->announcementBeingEdited->title;
        $beforeMessage = $this->announcementBeingEdited->message;

        // Get user's timezone for datetime-local conversion
        $userTimezone = Auth::user()->timezone ?? request()->cookie('timezone') ?? null;

        $this->announcementBeingEdited->update([
            'title' => $this->editAnnouncementForm['title'],
            'message' => $this->editAnnouncementForm['message'],
            'send_email' => $this->editAnnouncementForm['send_email'],
            'published_at' => $this->editAnnouncementForm['published_at'] ?
                $this->team->fromDateTimeLocal($this->editAnnouncementForm['published_at'], $userTimezone) : null,
            'target_roles' => ! empty($this->editAnnouncementForm['target_roles']) ?
                $this->editAnnouncementForm['target_roles'] : null,
        ]);

        $fieldChanges = [];

        if ($beforeTitle !== $this->announcementBeingEdited->title) {
            $fieldChanges['title'] = ['before' => $beforeTitle, 'after' => $this->announcementBeingEdited->title];
        }

        if ($beforeMessage !== $this->announcementBeingEdited->message) {
            $fieldChanges['message'] = ['before' => $beforeMessage, 'after' => $this->announcementBeingEdited->message];
        }

        if ($fieldChanges !== []) {
            CommunicationsAuditLogger::announcementUpdated($this->announcementBeingEdited, Auth::user(), $fieldChanges);
        }

        // Send emails if requested and announcement is now published (wasn't before)
        $isNowPublished = $this->announcementBeingEdited->isPublished();
        if ($this->announcementBeingEdited->send_email && $isNowPublished && ! $wasPublished) {
            $this->sendAnnouncementEmails($this->announcementBeingEdited);
        }

        $this->resetEditAnnouncementForm();
        $this->editingAnnouncement = false;

        $this->banner(__('Announcement updated successfully.'));

        // Refresh navigation menu to update announcement badge
        $this->dispatch('refresh-navigation-menu');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Confirm announcement deletion.
     *
     * @param  int  $announcementId
     * @return void
     */
    public function confirmAnnouncementDeletion($announcementId)
    {
        if (! SubscriptionEntitlementGate::allows($this->team)) {
            return;
        }

        $announcement = TeamAnnouncement::where('team_id', $this->team->id)
            ->findOrFail($announcementId);

        if ($announcement->created_by !== Auth::id() && ! Gate::check('postAnnouncements', $this->team)) {
            return;
        }

        $this->announcementBeingDeleted = $announcement;
        $this->confirmingAnnouncementDeletion = true;
    }

    /**
     * Delete the announcement.
     *
     * @return void
     */
    public function deleteAnnouncement()
    {
        if (! SubscriptionEntitlementGate::allows($this->team)) {
            return;
        }

        if ($this->announcementBeingDeleted && $this->announcementBeingDeleted->created_by !== Auth::id() && ! Gate::check('postAnnouncements', $this->team)) {
            return;
        }

        if ($this->announcementBeingDeleted) {
            CommunicationsAuditLogger::announcementDeleted($this->announcementBeingDeleted, Auth::user());
            $this->announcementBeingDeleted->delete();
        }

        $this->confirmingAnnouncementDeletion = false;
        $this->announcementBeingDeleted = null;

        $this->banner(__('Announcement deleted successfully.'));

        // Refresh navigation menu to update announcement badge
        $this->dispatch('refresh-navigation-menu');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Mark an announcement as read.
     *
     * @param  int  $announcementId
     * @return void
     */
    public function markAsRead($announcementId)
    {
        $announcement = TeamAnnouncement::where('team_id', $this->team->id)
            ->findOrFail($announcementId);

        $announcement->markAsReadBy(Auth::user());

        $this->dispatch('refresh-navigation-menu');
        $this->dispatch('refresh-notifications');
    }

    /**
     * Mark all announcements as read.
     *
     * @return void
     */
    public function markAllAsRead()
    {
        $unreadAnnouncements = TeamAnnouncement::getUnreadForUser(Auth::user());

        foreach ($unreadAnnouncements as $announcement) {
            $announcement->markAsReadBy(Auth::user());
        }

        $this->dispatch('refresh-navigation-menu');
        $this->dispatch('refresh-notifications');
        $this->banner(__('All announcements marked as read.'));
    }

    /**
     * Check if an announcement is unread.
     *
     * @param  \App\Models\TeamAnnouncement  $announcement
     * @return bool
     */
    public function isUnread($announcement)
    {
        return ! $announcement->hasBeenReadBy(Auth::user());
    }

    /**
     * Cancel announcement editing.
     *
     * @return void
     */
    public function cancelEditAnnouncement()
    {
        $this->resetErrorBag();
        $this->resetEditAnnouncementForm();
        $this->editingAnnouncement = false;
    }

    /**
     * Cancel announcement deletion.
     *
     * @return void
     */
    public function cancelAnnouncementDeletion()
    {
        $this->confirmingAnnouncementDeletion = false;
        $this->announcementBeingDeleted = null;
    }

    /**
     * Reset the create announcement form.
     *
     * @return void
     */
    public function resetCreateAnnouncementForm()
    {
        $this->resetErrorBag();
        $this->createAnnouncementForm = [
            'title' => '',
            'message' => '',
            'send_email' => false,
            'published_at' => '',
            'target_roles' => [],
        ];
    }

    /**
     * Reset the edit announcement form.
     *
     * @return void
     */
    public function resetEditAnnouncementForm()
    {
        $this->editAnnouncementForm = [
            'title' => '',
            'message' => '',
            'send_email' => false,
            'published_at' => '',
            'target_roles' => [],
        ];
        $this->announcementBeingEdited = null;
    }

    /**
     * Send announcement emails to eligible users.
     *
     * @param  \App\Models\TeamAnnouncement  $announcement
     */
    protected function sendAnnouncementEmails(TeamAnnouncement $announcement): void
    {
        $recipients = collect();
        $users = $this->team->allUsers()->filter(fn ($user) => $user && $user->email_verified_at !== null);

        foreach ($users as $user) {
            $shouldSend = $announcement->target_roles === null
                || empty($announcement->target_roles)
                || ! empty(array_intersect(
                    $announcement->target_roles,
                    $user->roles()->where('team_id', $this->team->id)->pluck('slug')->toArray()
                ));

            if ($shouldSend) {
                Mail::to($user)->send(new TeamAnnouncementMail($announcement));
                $recipients->push($user->email);
            }
        }

        $announcement->update(['emails_sent_at' => now()]);
    }

    /**
     * Get the announcements.
     *
     * @return LengthAwarePaginator
     */
    public function getAnnouncementsProperty()
    {
        $user = Auth::user();
        $canManageAnnouncements = SubscriptionEntitlementGate::allows($this->team)
            && Gate::check('postAnnouncements', $this->team);
        $teamId = $this->team->id;

        if ($canManageAnnouncements) {
            // Announcement authors see all announcements (published and drafts)
            return TeamAnnouncement::where('team_id', $teamId)
                ->with(['creator', 'readers', 'team.users' => function ($query) use ($teamId) {
                    $query->with(['roles' => function ($q) use ($teamId) {
                        $q->where('team_id', $teamId);
                    }]);
                }, 'team.owner' => function ($query) use ($teamId) {
                    $query->with(['roles' => function ($q) use ($teamId) {
                        $q->where('team_id', $teamId);
                    }]);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // Regular members see only published announcements relevant to their roles
            $userRoleSlugs = $user->roles()
                ->where('team_id', $teamId)
                ->pluck('slug')
                ->toArray();

            return TeamAnnouncement::published()
                ->where('team_id', $teamId)
                ->where(function ($query) use ($userRoleSlugs) {
                    $query->whereNull('target_roles')
                        ->orWhere(function ($q) use ($userRoleSlugs) {
                            if (! empty($userRoleSlugs)) {
                                foreach ($userRoleSlugs as $roleSlug) {
                                    $q->orWhereJsonContains('target_roles', $roleSlug);
                                }
                            }
                        });
                })
                ->with(['creator', 'readers', 'team.users' => function ($query) use ($teamId) {
                    $query->with(['roles' => function ($q) use ($teamId) {
                        $q->where('team_id', $teamId);
                    }]);
                }, 'team.owner' => function ($query) use ($teamId) {
                    $query->with(['roles' => function ($q) use ($teamId) {
                        $q->where('team_id', $teamId);
                    }]);
                }])
                ->orderBy('published_at', 'desc')
                ->paginate(10);
        }
    }

    /**
     * Get the unread count.
     *
     * @return int
     */
    public function getUnreadCountProperty()
    {
        return TeamAnnouncement::getUnreadCountForUser(Auth::user());
    }

    /**
     * Check if user can create announcements.
     *
     * @return bool
     */
    public function canCreateAnnouncements()
    {
        return SubscriptionEntitlementGate::allows($this->team)
            && Gate::check('postAnnouncements', $this->team);
    }

    /**
     * Get the available roles for this team.
     *
     * @return Collection
     */
    public function getRolesProperty()
    {
        return Role::orderBy('hierarchy')->get();
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render()
    {
        return view('afterburner-communications::announcements.announcement-manager');
    }
}
