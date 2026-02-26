<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleOAuthController extends Controller
{
    public function __construct(
        protected GoogleCalendarOAuthService $oauth
    ) {}

    /**
     * Redirect to Google's OAuth authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        try {
            $url = $this->oauth->buildAuthorizationUrl($request->user());

            return redirect()->away($url);
        } catch (\Exception $e) {
            Log::error('Failed to initiate Google OAuth', ['error' => $e->getMessage()]);

            return redirect('/admin')
                ->with('notification', [
                    'title' => 'Failed to connect to Google Calendar',
                    'status' => 'danger',
                ]);
        }
    }

    /**
     * Handle the OAuth callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning('Google OAuth error', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect('/admin')
                ->with('notification', [
                    'title' => 'Google Calendar connection declined',
                    'status' => 'warning',
                ]);
        }

        try {
            $this->oauth->handleCallback(
                code: $request->input('code'),
                state: $request->input('state'),
            );

            return redirect('/admin')
                ->with('notification', [
                    'title' => 'Google Calendar connected successfully',
                    'status' => 'success',
                ]);
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect('/admin')
                ->with('notification', [
                    'title' => 'Google Calendar connection failed',
                    'body' => $e->getMessage(),
                    'status' => 'danger',
                ]);
        }
    }
}
