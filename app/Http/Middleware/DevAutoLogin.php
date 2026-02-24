<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class DevAutoLogin
{
    /**
     * Automatically log in as User #1 in local development.
     * This middleware should NEVER be used in production.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && ! Auth::check()) {
            $user = User::first();

            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
