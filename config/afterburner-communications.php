<?php

return [

    'enabled' => env('AFTERBURNER_COMMUNICATIONS_ENABLED', true),

    'discussions' => [
        'enabled' => env('AFTERBURNER_COMMUNICATIONS_DISCUSSIONS_ENABLED', true),
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
            'team-announcements.*',
        ],
    ],

];
