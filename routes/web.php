<?php

use Afterburner\Communications\Models\DiscussionThread;
use App\Models\Team;
use App\Support\Afterburner;
use App\Support\Features;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    if (Afterburner::hasTeamFeatures()
        && config('afterburner-communications.announcements.enabled', true)
        && class_exists(Features::class)
        && Features::hasTeamAnnouncements()) {
        Route::get('/teams/{team}/announcements', function (Team $team) {
            return view('afterburner-communications::announcements.index', ['team' => $team]);
        })->middleware('can:view,team')->name('team-announcements.index');
    }

    if (config('afterburner-communications.discussions.enabled', true)) {
        Route::get('/teams/{team}/discussions', function (Team $team) {
            return view('afterburner-communications::discussions.index', ['team' => $team]);
        })->middleware('can:viewAny,'.DiscussionThread::class.',team')
            ->name('teams.discussions.index');

        Route::get('/teams/{team}/discussions/create', function (Team $team) {
            return view('afterburner-communications::discussions.create', ['team' => $team]);
        })->middleware('can:create,'.DiscussionThread::class.',team')
            ->name('teams.discussions.create');

        Route::get('/teams/{team}/discussions/{thread}', function (Team $team, DiscussionThread $thread) {
            abort_unless($thread->team_id === $team->id, 404);

            return view('afterburner-communications::discussions.show', ['team' => $team, 'thread' => $thread]);
        })->middleware('can:view,thread')
            ->whereNumber('thread')
            ->name('teams.discussions.show');
    }

    if (config('afterburner-communications.communication_log.enabled', true)) {
        Route::get('/teams/{team}/communication-log', function (Team $team) {
            return view('afterburner-communications::communications.log-index', ['team' => $team]);
        })->middleware('can:viewCommunicationLog,team')
            ->name('teams.communication-log.index');
    }
});
