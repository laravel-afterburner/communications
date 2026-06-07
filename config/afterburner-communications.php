<?php

use App\Models\Property;
use App\Support\CouncilRoles;

return [

    'enabled' => true,

    'discussions' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Council roles (discussion scope: council)
    |--------------------------------------------------------------------------
    */
    'council_role_resolver' => env('AFTERBURNER_COUNCIL_ROLE_RESOLVER', CouncilRoles::class),

    'council_role_slugs' => [
        'president',
        'treasurer',
        'secretary',
        'council_member',
    ],

    'property_model' => Property::class,

    'audit' => [
        'skip_routes' => [
            'teams.discussions.*',
            'team-announcements.*',
        ],
    ],

];
