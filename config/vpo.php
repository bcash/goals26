<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPO REST API
    |--------------------------------------------------------------------------
    |
    | Configuration for the VPO (Virtual Practice Office) REST API.
    | Solas Rún connects as a Sanctum-authenticated HTTP client.
    |
    */

    'base_url' => env('VPO_API_URL', 'https://vpo.alp1n3.com/api/v1'),

    'token' => env('VPO_API_TOKEN'),

    'timeout' => 30,

    // Data cache time-to-live in seconds (5 minutes)
    'cache_ttl' => 300,

    'enabled' => env('VPO_ENABLED', true),

];
