<?php

namespace App\Http\Controllers;

use App\Services\GranolaOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GranolaOAuthController extends Controller
{
    public function __construct(
        protected GranolaOAuthService $oauth
    ) {}

    /**
     * Redirect to Granola's OAuth authorization page.
     */
    public function redirect(Request $request): RedirectResponse
    {
        try {
            $url = $this->oauth->buildAuthorizationUrl($request->user());

            return redirect()->away($url);
        } catch (\Exception $e) {
            Log::error('Failed to initiate Granola OAuth', ['error' => $e->getMessage()]);

            return redirect()
                ->route('filament.admin.resources.client-meetings.index')
                ->with('notification', [
                    'title' => 'Failed to connect to Granola',
                    'status' => 'danger',
                ]);
        }
    }

    /**
     * Handle the OAuth callback from Granola.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            Log::warning('Granola OAuth error', [
                'error' => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect()
                ->route('filament.admin.resources.client-meetings.index')
                ->with('notification', [
                    'title' => 'Granola connection declined',
                    'status' => 'warning',
                ]);
        }

        try {
            $this->oauth->handleCallback(
                code: $request->input('code'),
                state: $request->input('state'),
            );

            return redirect()
                ->route('filament.admin.resources.client-meetings.index')
                ->with('notification', [
                    'title' => 'Granola connected successfully',
                    'status' => 'success',
                ]);
        } catch (\Exception $e) {
            Log::error('Granola OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect()
                ->route('filament.admin.resources.client-meetings.index')
                ->with('notification', [
                    'title' => 'Failed to connect Granola',
                    'status' => 'danger',
                ]);
        }
    }
}
