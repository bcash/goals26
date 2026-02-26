<?php

namespace App\Services;

use App\Models\GranolaToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GranolaOAuthService
{
    private string $authUrl;

    private string $mcpUrl;

    private string $scopes;

    public function __construct()
    {
        $this->authUrl = config('services.granola.auth_url', 'https://mcp-auth.granola.ai');
        $this->mcpUrl = config('services.granola.mcp_url', 'https://mcp.granola.ai/mcp');
        $this->scopes = config('services.granola.scopes', 'offline_access openid');
    }

    /**
     * Register a client via OAuth 2.0 Dynamic Client Registration (DCR).
     * Cached per-app since client credentials don't change per-user.
     *
     * @return array{client_id: string, client_secret: string|null}
     */
    public function registerClient(): array
    {
        return Cache::remember('granola:dcr:client', 86400, function () {
            $redirectUri = $this->buildRedirectUri();

            $response = Http::post("{$this->authUrl}/oauth2/register", [
                'client_name' => 'Solas Rún',
                'grant_types' => ['authorization_code', 'refresh_token'],
                'redirect_uris' => [$redirectUri],
                'response_types' => ['code'],
                'token_endpoint_auth_method' => 'client_secret_post',
                'scope' => $this->scopes,
            ]);

            if ($response->failed()) {
                Log::error('Granola DCR failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException("Granola DCR failed: HTTP {$response->status()}");
            }

            $data = $response->json();

            return [
                'client_id' => $data['client_id'],
                'client_secret' => $data['client_secret'] ?? null,
            ];
        });
    }

    /**
     * Build the OAuth authorization URL and store PKCE verifier in session.
     */
    public function buildAuthorizationUrl(User $user): string
    {
        $client = $this->registerClient();

        // Generate PKCE code verifier and challenge (S256)
        $codeVerifier = Str::random(128);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $state = Str::random(40);

        // Store in session for callback verification
        session([
            'granola_oauth_state' => $state,
            'granola_oauth_code_verifier' => $codeVerifier,
            'granola_oauth_user_id' => $user->id,
            'granola_oauth_client_id' => $client['client_id'],
            'granola_oauth_client_secret' => $client['client_secret'],
        ]);

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $this->buildRedirectUri(),
            'scope' => $this->scopes,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return "{$this->authUrl}/oauth2/authorize?{$params}";
    }

    /**
     * Handle the OAuth callback: exchange code for tokens and store them.
     */
    public function handleCallback(string $code, string $state): GranolaToken
    {
        // Verify state
        $sessionState = session('granola_oauth_state');
        if (! $sessionState || $sessionState !== $state) {
            throw new \RuntimeException('Invalid OAuth state parameter');
        }

        $codeVerifier = session('granola_oauth_code_verifier');
        $userId = session('granola_oauth_user_id');
        $clientId = session('granola_oauth_client_id');
        $clientSecret = session('granola_oauth_client_secret');

        // Clear session values
        session()->forget([
            'granola_oauth_state',
            'granola_oauth_code_verifier',
            'granola_oauth_user_id',
            'granola_oauth_client_id',
            'granola_oauth_client_secret',
        ]);

        // Exchange code for tokens
        $response = Http::asForm()->post("{$this->authUrl}/oauth2/token", array_filter([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->buildRedirectUri(),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code_verifier' => $codeVerifier,
        ]));

        if ($response->failed()) {
            Log::error('Granola token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("Granola token exchange failed: HTTP {$response->status()}");
        }

        $data = $response->json();

        return GranolaToken::updateOrCreate(
            ['user_id' => $userId],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                'scopes' => $data['scope'] ?? $this->scopes,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]
        );
    }

    /**
     * Refresh an expired token using the refresh_token grant.
     */
    public function refreshToken(GranolaToken $token): GranolaToken
    {
        if (! $token->refresh_token) {
            throw new \RuntimeException('No refresh token available — user must re-authorize');
        }

        $response = Http::asForm()->post("{$this->authUrl}/oauth2/token", array_filter([
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token,
            'client_id' => $token->client_id,
            'client_secret' => $token->client_secret,
        ]));

        if ($response->failed()) {
            Log::warning('Granola token refresh failed', [
                'status' => $response->status(),
                'user_id' => $token->user_id,
            ]);

            // If refresh fails, delete the token so user can re-authorize
            $token->delete();

            throw new \RuntimeException("Granola token refresh failed: HTTP {$response->status()}");
        }

        $data = $response->json();

        $token->update([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $token->refresh_token,
            'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            'scopes' => $data['scope'] ?? $token->scopes,
        ]);

        return $token->fresh();
    }

    /**
     * Get a valid access token for a user, refreshing if needed.
     * Returns null if user has no Granola connection.
     */
    public function getValidToken(User $user): ?string
    {
        $token = $user->granolaToken;

        if (! $token) {
            return null;
        }

        if ($token->needsRefresh()) {
            try {
                $token = $this->refreshToken($token);
            } catch (\Exception $e) {
                Log::warning('Could not refresh Granola token', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $token->access_token;
    }

    /**
     * Revoke/disconnect a user's Granola connection.
     */
    public function revokeToken(User $user): void
    {
        $user->granolaToken?->delete();
    }

    /**
     * Check if a user has an active Granola connection.
     */
    public function isConnected(User $user): bool
    {
        $token = $user->granolaToken;

        if (! $token) {
            return false;
        }

        // Connected if token exists and either not expired or has a refresh token
        return ! $token->isExpired() || $token->refresh_token !== null;
    }

    /**
     * Build the full redirect URI for OAuth callbacks.
     */
    private function buildRedirectUri(): string
    {
        $path = config('services.granola.redirect_uri', '/granola/callback');

        return url($path);
    }
}
