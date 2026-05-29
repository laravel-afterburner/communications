<?php

return [

    'enabled' => env('AFTERBURNER_COMMUNICATIONS_ENABLED', true),

    'announcements' => [
        'enabled' => env('AFTERBURNER_COMMUNICATIONS_ANNOUNCEMENTS_ENABLED', true),
    ],

    'discussions' => [
        'enabled' => env('AFTERBURNER_COMMUNICATIONS_DISCUSSIONS_ENABLED', true),
    ],

    'communication_log' => [
        'enabled' => env('AFTERBURNER_COMMUNICATIONS_LOG_ENABLED', true),
        'log_notification_mail' => true,
        'log_notification_database' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Council role slugs (discussion scope: council)
    |--------------------------------------------------------------------------
    */
    'council_role_slugs' => [
        'president',
        'treasurer',
        'secretary',
        'council_member',
    ],

    'property_model' => env('AFTERBURNER_PROPERTY_MODEL', \App\Models\Property::class),

    'audit' => [
        'skip_routes' => [
            'teams.discussions.*',
            'teams.communication-log.*',
            'team-announcements.*',
        ],
    ],

];
