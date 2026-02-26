<?php

namespace Tests\Feature;

use App\Services\CloudwaysApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudwaysApiClientTest extends TestCase
{
    use RefreshDatabase;

    private CloudwaysApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new CloudwaysApiClient;
        config([
            'services.cloudways.base_url' => 'https://api.cloudways.com/api/v1',
            'services.cloudways.email' => 'test@example.com',
            'services.cloudways.api_key' => 'test-api-key',
            'services.cloudways.token_ttl' => 3300,
        ]);
        Cache::forget('cloudways:access_token');
    }

    public function test_get_access_token_fetches_and_caches(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response([
                'access_token' => 'fresh-token-123',
            ]),
        ]);

        $token = $this->client->getAccessToken();

        $this->assertEquals('fresh-token-123', $token);
        $this->assertEquals('fresh-token-123', Cache::get('cloudways:access_token'));
    }

    public function test_get_access_token_uses_cache(): void
    {
        Cache::put('cloudways:access_token', 'cached-token-456', 3300);

        Http::fake(); // No HTTP calls should be made

        $token = $this->client->getAccessToken();

        $this->assertEquals('cached-token-456', $token);
        Http::assertNothingSent();
    }

    public function test_refresh_access_token_clears_and_recaches(): void
    {
        Cache::put('cloudways:access_token', 'old-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response([
                'access_token' => 'new-token-789',
            ]),
        ]);

        $token = $this->client->refreshAccessToken();

        $this->assertEquals('new-token-789', $token);
        $this->assertEquals('new-token-789', Cache::get('cloudways:access_token'));
    }

    public function test_refresh_throws_on_failure(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to obtain Cloudways access token');

        $this->client->refreshAccessToken();
    }

    public function test_list_servers_returns_server_data(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [
                    ['id' => 743387, 'label' => 'app76', 'public_ip' => '144.202.98.245'],
                    ['id' => 999999, 'label' => 'app82', 'public_ip' => '10.0.0.1'],
                ],
            ]),
        ]);

        $servers = $this->client->listServers();

        $this->assertCount(2, $servers);
        $this->assertEquals(743387, $servers[0]['id']);
    }

    public function test_get_server_apps_returns_apps_for_server(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [
                    [
                        'id' => 743387,
                        'label' => 'app76',
                        'apps' => [
                            ['id' => '100', 'label' => 'alpine23', 'cname' => 'alpine-abc.cloudwaysapps.com'],
                            ['id' => '101', 'label' => 'eaca25', 'cname' => 'eaca-xyz.cloudwaysapps.com'],
                        ],
                    ],
                ],
            ]),
        ]);

        $apps = $this->client->getServerApps(743387);

        $this->assertCount(2, $apps);
        $this->assertEquals('alpine23', $apps[0]['label']);
    }

    public function test_get_server_apps_returns_empty_for_unknown_server(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [
                    ['id' => 743387, 'label' => 'app76', 'apps' => []],
                ],
            ]),
        ]);

        $apps = $this->client->getServerApps(999999);

        $this->assertEquals([], $apps);
    }

    public function test_clone_app_returns_operation_id(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/app/clone' => Http::response([
                'operation_id' => 'op-12345',
            ]),
        ]);

        $operationId = $this->client->cloneApp(743387, '100', 'alpine23', 999999);

        $this->assertEquals('op-12345', $operationId);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['server_id'] === 743387
                && $body['app_id'] === '100'
                && $body['destination_server_id'] === 999999;
        });
    }

    public function test_clone_app_throws_when_no_operation_id(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/app/clone' => Http::response(['status' => 'error']),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no operation_id');

        $this->client->cloneApp(743387, '100', 'alpine23', 999999);
    }

    public function test_get_operation_status_returns_data(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/operation/op-12345*' => Http::response([
                'operation' => [
                    'id' => 'op-12345',
                    'status' => 'Operation completed',
                    'is_completed' => 1,
                ],
            ]),
        ]);

        $result = $this->client->getOperationStatus('op-12345');

        $this->assertEquals('Operation completed', $result['status']);
    }

    public function test_list_servers_returns_empty_on_failure(): void
    {
        Cache::put('cloudways:access_token', 'test-token', 3300);

        Http::fake([
            'api.cloudways.com/api/v1/server*' => Http::response('Server Error', 500),
        ]);

        $result = $this->client->listServers();

        $this->assertEquals([], $result);
    }

    public function test_connection_test_returns_true_on_success(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response([
                'access_token' => 'test-token',
            ]),
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [['id' => 1]],
            ]),
        ]);

        $this->assertTrue($this->client->testConnection());
    }

    public function test_connection_test_returns_false_on_failure(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response('Unauthorized', 401),
        ]);

        $this->assertFalse($this->client->testConnection());
    }
}
