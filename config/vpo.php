<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPO MCP Server
    |--------------------------------------------------------------------------
    |
    | Configuration for the VPO (Virtual Practice Office) MCP server.
    | Solas Rún connects as a JSON-RPC 2.0 client over HTTPS.
    |
    */

    'url' => env('VPO_MCP_URL', 'https://vpo.alp1n3.com/api/mcp/vpo'),

    'key' => env('VPO_MCP_KEY'),

    'timeout' => 30,

    // MCP session ID time-to-live in seconds (30 minutes)
    'session_ttl' => 1800,

    // Data cache time-to-live in seconds (5 minutes)
    'cache_ttl' => 300,

    'enabled' => env('VPO_ENABLED', true),

];
