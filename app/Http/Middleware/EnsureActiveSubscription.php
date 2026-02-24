<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        // In development mode, always allow access
        if (app()->environment('local')) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $user->hasActiveAccess()) {
            return $next($request);
        }

        return redirect()->route('filament.admin.auth.login')
            ->with('error', 'Your subscription has expired.');
    }
}
