<?php

use Afterburner\Communications\Mail\TeamAnnouncementMail;
use Afterburner\Communications\Models\DiscussionThread;
use Afterburner\Communications\Models\TeamAnnouncement;
use App\Models\Team;
use App\Support\Afterburner;
use Illuminate\Support\Facades\Route;


Route::middleware(['web', 'auth', 'verified'])->group(function () {
    if (Afterburner::hasTeamFeatures()) {
        Route::get('/' . entity_url_slug() . '/{team}/announcements', function (Team $team) {
            return view('afterburner-communications::announcements.index', ['team' => $team]);
        })->middleware('can:viewAny,'.TeamAnnouncement::class.',team')
            ->name('team-announcements.index');
    }

    if (config('afterburner-communications.discussions.enabled', true)) {
        Route::get('/' . entity_url_slug() . '/{team}/discussions', function (Team $team) {
            return view('afterburner-communications::discussions.index', ['team' => $team]);
        })->middleware('can:viewAny,'.DiscussionThread::class.',team')
            ->name('teams.discussions.index');

        Route::get('/' . entity_url_slug() . '/{team}/discussions/create', function (Team $team) {
            return view('afterburner-communications::discussions.create', ['team' => $team]);
        })->middleware('can:create,'.DiscussionThread::class.',team')
            ->name('teams.discussions.create');

        Route::get('/' . entity_url_slug() . '/{team}/discussions/{thread}', function (Team $team, DiscussionThread $thread) {
            abort_unless($thread->team_id === $team->id, 404);

            return view('afterburner-communications::discussions.show', ['team' => $team, 'thread' => $thread]);
        })->middleware('can:view,thread')
            ->whereNumber('thread')
            ->name('teams.discussions.show');
    }

    if (app()->environment('local', 'development')) {
        Route::get('/preview-email/team-announcement', function () {
            $team = Team::first();
            $announcement = TeamAnnouncement::first();

            if (! $announcement && $team) {
                $announcement = new TeamAnnouncement([
                    'title' => 'New Announcement',
                    'message' => 'Here is a brand new announcement.',
                    'team_id' => $team->id,
                    'created_by' => $team->user_id,
                ]);
            }

            if (! $team || ! $announcement) {
                return 'No team or announcement found. Please create test data first.';
            }

            return new TeamAnnouncementMail($announcement);
        });
    }
});
