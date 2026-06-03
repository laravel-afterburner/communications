@component('mail::message', ['team' => $team])

# {{ $announcement->title }}

{!! nl2br(e($announcement->message)) !!}

@component('mail::button', ['url' => route('dashboard')])
View Dashboard
@endcomponent

{{ __('This is an automated announcement from :team.', ['team' => $team->name]) }}

@endcomponent

