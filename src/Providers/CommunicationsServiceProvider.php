<?php

namespace Afterburner\Communications\Providers;

use Afterburner\Communications\Console\Commands\InstallCommand;
use Afterburner\Communications\Console\Commands\SendScheduledAnnouncements;
use Afterburner\Communications\Database\Seeders\CommunicationsPermissionsSeeder;
use Afterburner\Communications\Events\ThreadCreated;
use Afterburner\Communications\Listeners\LogCommunicationsAudit;
use Afterburner\Communications\Livewire\Announcements\AnnouncementManager;
use Afterburner\Communications\Livewire\Discussions\Create as DiscussionsCreate;
use Afterburner\Communications\Livewire\Discussions\Index as DiscussionsIndex;
use Afterburner\Communications\Livewire\Discussions\Show as DiscussionsShow;
use Afterburner\Communications\Models\DiscussionPost;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Models\TeamAnnouncement;
use Afterburner\Communications\Policies\DiscussionPostPolicy;
use Afterburner\Communications\Policies\DiscussionThreadPolicy;
use Afterburner\Communications\Policies\TeamAnnouncementPolicy;
use Afterburner\Communications\Support\CommunicationsPermissionGroups;
use Afterburner\Communications\Support\DiscussionNotificationService;
use Afterburner\Playbook\Support\Playbook;
use App\Models\Team;
use App\Support\Audit\AuditCategories;
use App\Support\DashboardSections;
use App\Support\Navigation;
use App\Support\NavigationActive;
use App\Support\PackageSeederRegistry;
use App\Support\PermissionGroupsRegistry;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CommunicationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! class_exists(Team::class)) {
            return;
        }

        $this->mergeConfigFrom(
            __DIR__.'/../../config/afterburner-communications.php',
            'afterburner-communications'
        );

    }

    public function boot(): void
    {
        if (! class_exists(Team::class) || ! config('afterburner-communications.enabled', true)) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/afterburner-communications.php' => config_path('afterburner-communications.php'),
        ], 'afterburner-communications-config');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/afterburner-communications'),
        ], 'afterburner-communications-assets');

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'afterburner-communications');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');

        $this->registerLivewireComponents();
        $this->registerPolicies();
        $this->registerGates();
        $this->registerAuditSkipRoutes();
        $this->registerAuditCategories();
        $this->registerAuditListeners();
        $this->registerNavigation();
        $this->registerDashboardSections();
        $this->registerPermissionGroups();
        $this->registerPlaybook();
        $this->registerPackageSeeder();
        $this->registerSubscriptionPackageFeatures();
        $this->registerSchedule();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SendScheduledAnnouncements::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('team-announcements.announcement-manager', AnnouncementManager::class);

        if (config('afterburner-communications.discussions.enabled', true)) {
            Livewire::component('discussions.index', DiscussionsIndex::class);
            Livewire::component('discussions.show', DiscussionsShow::class);
            Livewire::component('discussions.create', DiscussionsCreate::class);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(DiscussionThread::class, DiscussionThreadPolicy::class);
        Gate::policy(DiscussionPost::class, DiscussionPostPolicy::class);
        Gate::policy(TeamAnnouncement::class, TeamAnnouncementPolicy::class);
    }

    protected function registerGates(): void
    {
        Gate::define('postAnnouncements', function ($user, Team $team) {
            return app(TeamAnnouncementPolicy::class)->create($user, $team);
        });
    }

    protected function registerAuditSkipRoutes(): void
    {
        if (! config()->has('audit.skip_routes')) {
            return;
        }

        $skipRoutes = config('afterburner-communications.audit.skip_routes', []);

        config([
            'audit.skip_routes' => array_values(array_unique(array_merge(
                config('audit.skip_routes', []),
                $skipRoutes
            ))),
        ]);
    }

    protected function registerAuditCategories(): void
    {
        if (! class_exists(AuditCategories::class)) {
            return;
        }

        AuditCategories::register([
            'discussion' => 'Discussion',
            'announcement' => 'Announcement',
        ]);
    }

    protected function registerAuditListeners(): void
    {
        if (! config('audit.enabled', true)) {
            return;
        }

        Event::listen(ThreadCreated::class, [LogCommunicationsAudit::class, 'handleThreadCreated']);
    }

    protected function registerNavigation(): void
    {
        if (! class_exists(Navigation::class)) {
            return;
        }

        $children = [];

        if (config('afterburner-communications.discussions.enabled', true)) {
            $children[] = [
                'label' => 'Discussions',
                'route' => 'teams.discussions.index',
                'route_params' => fn () => ['team' => auth()->user()?->currentTeam?->id],
                'permission' => fn ($user) => $user?->currentTeam
                    && $user->can('viewAny', [DiscussionThread::class, $user->currentTeam]),
                'active' => fn () => NavigationActive::routeIs('teams.discussions.*'),
                'badge' => fn () => auth()->user()
                    ? DiscussionNotificationService::getUnreadCountForUser(auth()->user())
                    : 0,
            ];
        }

        $children[] = [
            'label' => 'Announcements',
            'route' => 'team-announcements.index',
            'route_params' => fn () => ['team' => auth()->user()?->currentTeam?->id],
            'permission' => fn ($user) => $user?->currentTeam
                && $user->can('viewAny', [TeamAnnouncement::class, $user->currentTeam]),
            'active' => fn () => NavigationActive::routeIs('team-announcements.*'),
            'badge' => fn () => auth()->user()
                ? TeamAnnouncement::getUnreadCountForUser(auth()->user())
                : 0,
        ];

        if ($children !== []) {
            Navigation::register([
                'label' => 'Communications',
                'icon' => 'chat-bubble-left-right',
                'order' => 25,
                'children' => $children,
                'active' => fn () => NavigationActive::routeIs('teams.discussions.*')
                    || NavigationActive::routeIs('team-announcements.*'),
            ]);
        }
    }

    protected function registerPermissionGroups(): void
    {
        if (! class_exists(PermissionGroupsRegistry::class)) {
            return;
        }

        foreach (CommunicationsPermissionGroups::definitions() as $label => $slugs) {
            PermissionGroupsRegistry::register($label, $slugs);
        }
    }

    protected function registerDashboardSections(): void
    {
        if (! class_exists(DashboardSections::class)) {
            return;
        }

        DashboardSections::register([
            'key' => 'zone.schedule.announcements',
            'label' => 'Announcements',
            'description' => 'Recent community announcements.',
            'group' => 'Schedule',
            'group_order' => 40,
            'order' => 20,
            'available' => fn () => config('afterburner-communications.announcements.enabled', true),
        ]);
    }

    protected function registerPlaybook(): void
    {
        if (! class_exists(Playbook::class)) {
            return;
        }

        Playbook::register([
            'key' => 'communications',
            'label' => 'Communications',
            'order' => 30,
            'path' => __DIR__.'/../../playbook',
            'enabled' => fn () => config('afterburner-communications.enabled', true),
            'permission' => fn ($user) => $user?->currentTeam !== null,
        ]);
    }

    protected function registerPackageSeeder(): void
    {
        if (class_exists(PackageSeederRegistry::class)) {
            PackageSeederRegistry::register(CommunicationsPermissionsSeeder::class);
        }
    }

    protected function registerSubscriptionPackageFeatures(): void
    {
        if (! class_exists(\Afterburner\Subscriptions\Support\SubscriptionPackageFeatures::class)) {
            return;
        }

        \Afterburner\Subscriptions\Support\SubscriptionPackageFeatures::register('communications', 'Communications', [
            'Announcements',
            'Discussions',
        ]);
    }

    protected function registerSchedule(): void
    {
        $this->app->booted(function () {
            Schedule::command('announcements:send-scheduled')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->description('Send scheduled announcement emails');
        });
    }
}
