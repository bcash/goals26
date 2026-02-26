<?php

namespace App\Services;

use App\Models\GoogleToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleCalendarOAuthService
{
    private string $clientId;

    private string $clientSecret;

    private string $scopes;

    public function __construct()
    {
        $this->clientId = config('services.google.client_id') ?? '';
        $this->clientSecret = config('services.google.client_secret') ?? '';
        $this->scopes = config('services.google.scopes') ?? 'https://www.googleapis.com/auth/calendar.readonly';
    }

    /**
     * Build the Google OAuth authorization URL.
     */
    public function buildAuthorizationUrl(User $user): string
    {
        $state = Str::random(40);
        session(['google_oauth_state' => $state, 'google_oauth_user_id' => $user->id]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->buildRedirectUri(),
            'response_type' => 'code',
            'scope' => $this->scopes,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);
    }

    /**
     * Handle the OAuth callback: exchange code for tokens and store them.
     */
    public function handleCallback(string $code, string $state): GoogleToken
    {
        $sessionState = session('google_oauth_state');
        $userId = session('google_oauth_user_id');

        if (! $sessionState || $state !== $sessionState) {
            throw new \RuntimeException('Invalid OAuth state parameter.');
        }

        session()->forget(['google_oauth_state', 'google_oauth_user_id']);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->buildRedirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to exchange authorization code for tokens.');
        }

        $data = $response->json();

        return GoogleToken::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                'scopes' => $data['scope'] ?? $this->scopes,
            ]
        );
    }

    /**
     * Refresh an expired token using the refresh_token grant.
     */
    public function refreshToken(GoogleToken $token): GoogleToken
    {
        if (! $token->refresh_token) {
            throw new \RuntimeException('No refresh token available. User must re-authorize.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $token->refresh_token,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            Log::error('Google OAuth token refresh failed', ['status' => $response->status()]);
            throw new \RuntimeException('Failed to refresh Google access token.');
        }

        $data = $response->json();

        $token->update([
            'access_token' => $data['access_token'],
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $token->refresh();
    }

    /**
     * Get a valid access token for a user, refreshing if needed.
     * Returns null if user has no Google connection.
     */
    public function getValidToken(User $user): ?string
    {
        $token = $user->googleToken;

        if (! $token) {
            return null;
        }

        if ($token->needsRefresh()) {
            try {
                $token = $this->refreshToken($token);
            } catch (\Exception $e) {
                Log::warning('Google token refresh failed for user '.$user->id, ['error' => $e->getMessage()]);

                return null;
            }
        }

        return $token->access_token;
    }

    /**
     * Check if a user has an active Google connection.
     */
    public function isConnected(User $user): bool
    {
        $token = $user->googleToken;

        if (! $token) {
            return false;
        }

        return ! $token->isExpired() || $token->refresh_token !== null;
    }

    /**
     * Revoke/disconnect a user's Google connection.
     */
    public function revokeToken(User $user): void
    {
        $token = $user->googleToken;

        if ($token) {
            try {
                Http::get('https://oauth2.googleapis.com/revoke', ['token' => $token->access_token]);
            } catch (\Exception $e) {
                // Best effort revocation
            }

            $token->delete();
        }
    }

    /**
     * Build the full redirect URI for OAuth callbacks.
     */
    private function buildRedirectUri(): string
    {
        $path = config('services.google.redirect_uri', '/google/callback');

        return url($path);
    }
}
