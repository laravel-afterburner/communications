@component('mail::message', ['team' => $team])

@slot('header')

@component('mail::header', ['url' => config('app.url'), 'team' => $team, 'teamLogo' => $teamLogo ?? null])

{{ $team->name }}

@endcomponent

@endslot

# {{ $announcement->title }}

{!! nl2br(e($announcement->message)) !!}

@component('mail::button', ['url' => route('dashboard')])
View Dashboard
@endcomponent

{{ __('This is an automated announcement from :team.', ['team' => $team->name]) }}

@endcomponent

