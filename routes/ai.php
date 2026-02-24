<?php

use App\Mcp\Servers\SolasRunServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI Routes (MCP Servers)
|--------------------------------------------------------------------------
|
| Register MCP servers here. Local servers use STDIO transport and are
| started via `php artisan mcp:start {handle}`. Web servers use HTTP
| transport and are available at the registered route.
|
*/

Mcp::local('solas-run', SolasRunServer::class);
