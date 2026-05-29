<?php

namespace Afterburner\Communications\Providers;

use Afterburner\Communications\Console\Commands\InstallCommand;
use Afterburner\Communications\Console\Commands\SendScheduledAnnouncements;
use Afterburner\Communications\Database\Seeders\CommunicationsPermissionsSeeder;
use Afterburner\Communications\Listeners\LogNotificationCommunication;
use Afterburner\Communications\Livewire\Announcements\AnnouncementManager;
use Afterburner\Communications\Livewire\Communications\LogIndex;
use Afterburner\Communications\Livewire\Discussions\Create as DiscussionsCreate;
use Afterburner\Communications\Livewire\Discussions\Index as DiscussionsIndex;
use Afterburner\Communications\Livewire\Discussions\Show as DiscussionsShow;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Policies\CommunicationLogPolicy;
use Afterburner\Communications\Policies\DiscussionThreadPolicy;
use Afterburner\Communications\Services\CommunicationLogService;
use App\Models\Team;
use App\Support\Navigation;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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

        $this->app->singleton(CommunicationLogService::class);
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
        $this->registerNavigation();
        $this->registerEventListeners();
        $this->registerPackageSeeder();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SendScheduledAnnouncements::class,
            ]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        if (config('afterburner-communications.announcements.enabled', true)) {
            Livewire::component('team-announcements.announcement-manager', AnnouncementManager::class);
        }

        if (config('afterburner-communications.discussions.enabled', true)) {
            Livewire::component('discussions.index', DiscussionsIndex::class);
            Livewire::component('discussions.show', DiscussionsShow::class);
            Livewire::component('discussions.create', DiscussionsCreate::class);
        }

        if (config('afterburner-communications.communication_log.enabled', true)) {
            Livewire::component('communications.log-index', LogIndex::class);
        }
    }

    protected function registerPolicies(): void
    {
        Gate::policy(DiscussionThread::class, DiscussionThreadPolicy::class);
    }

    protected function registerGates(): void
    {
        Gate::define('viewCommunicationLog', function ($user, Team $team) {
            return app(CommunicationLogPolicy::class)->viewAny($user, $team);
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
                'active' => fn () => request()->routeIs('teams.discussions.*'),
            ];
        }

        if (config('afterburner-communications.communication_log.enabled', true)) {
            $children[] = [
                'label' => 'Chat Log',
                'route' => 'teams.communication-log.index',
                'route_params' => fn () => ['team' => auth()->user()?->currentTeam?->id],
                'permission' => fn ($user) => $user?->currentTeam
                    && $user->can('viewCommunicationLog', $user->currentTeam),
                'active' => fn () => request()->routeIs('teams.communication-log.*'),
            ];
        }

        if ($children !== []) {
            Navigation::register([
                'label' => 'Chat',
                'icon' => 'chat-bubble-left-right',
                'order' => 25,
                'children' => $children,
                'active' => fn () => request()->routeIs('teams.discussions.*')
                    || request()->routeIs('teams.communication-log.*'),
            ]);
        }
    }

    protected function registerEventListeners(): void
    {
        if (config('afterburner-communications.communication_log.enabled', true)) {
            Event::listen(NotificationSent::class, LogNotificationCommunication::class);
        }
    }

    protected function registerPackageSeeder(): void
    {
        if (class_exists(\App\Support\PackageSeederRegistry::class)) {
            \App\Support\PackageSeederRegistry::register(CommunicationsPermissionsSeeder::class);
        }
    }
}
