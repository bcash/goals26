<?php

namespace Tests\Feature;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ServerMigrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cloudways.base_url' => 'https://api.cloudways.com/api/v1',
            'services.cloudways.email' => 'test@example.com',
            'services.cloudways.api_key' => 'test-key',
            'services.cloudways.source_server_id' => 743387,
            'services.cloudways.source_server_ip' => '144.202.98.245',
            'services.cloudways.target_server_id' => 999999,
            'services.cloudways.target_server_ip' => '10.0.0.1',
            'services.cloudways.token_ttl' => 3300,
            'services.cloudflare.base_url' => 'https://api.cloudflare.com/client/v4',
            'services.cloudflare.api_token' => 'test-cf-token',
        ]);

        $this->service = app(ServerMigrationService::class);
    }

    // ── Inventory ─────────────────────────────────────────────────────

    public function test_inventory_creates_tracking_records(): void
    {
        $this->fakeCloudwaysWithApps();

        $records = $this->service->inventory();

        $this->assertCount(2, $records);
        $this->assertDatabaseCount('server_migration_apps', 2);
        $this->assertDatabaseHas('server_migration_apps', [
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
        ]);
    }

    public function test_inventory_is_idempotent(): void
    {
        $this->fakeCloudwaysWithApps();

        $this->service->inventory();
        $this->service->inventory(); // Run again

        $this->assertDatabaseCount('server_migration_apps', 2);
    }

    public function test_inventory_returns_empty_when_no_apps(): void
    {
        $this->fakeCloudwaysAuth();
        Http::fake([
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [
                    ['id' => 743387, 'label' => 'app76', 'apps' => []],
                ],
            ]),
        ]);

        $records = $this->service->inventory();

        $this->assertTrue($records->isEmpty());
    }

    // ── DNS Switch ────────────────────────────────────────────────────

    public function test_switch_dns_updates_a_records(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'cloned',
            'should_migrate' => true,
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/zones?*' => Http::response([
                'result' => [['id' => 'zone-abc', 'name' => 'alpine.io']],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records*' => Http::response([
                'result' => [
                    ['id' => 'rec-1', 'type' => 'A', 'name' => 'alpine.io', 'content' => '144.202.98.245', 'proxied' => true],
                    ['id' => 'rec-2', 'type' => 'A', 'name' => 'www.alpine.io', 'content' => '144.202.98.245', 'proxied' => true],
                ],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records/rec-1' => Http::response([
                'result' => ['id' => 'rec-1', 'content' => '10.0.0.1'],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records/rec-2' => Http::response([
                'result' => ['id' => 'rec-2', 'content' => '10.0.0.1'],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/purge_cache' => Http::response([
                'success' => true,
            ]),
        ]);

        $result = $this->service->switchDns($app);

        $this->assertEquals('dns_switched', $result->status);
        $this->assertNotNull($result->dns_switched_at);
        $this->assertCount(2, $result->dns_records_updated);
        $this->assertEquals('144.202.98.245', $result->dns_records_updated[0]['old_ip']);
        $this->assertEquals('10.0.0.1', $result->dns_records_updated[0]['new_ip']);
    }

    public function test_switch_dns_dry_run_makes_no_changes(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'cloned',
            'should_migrate' => true,
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/zones?*' => Http::response([
                'result' => [['id' => 'zone-abc', 'name' => 'alpine.io']],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records*' => Http::response([
                'result' => [
                    ['id' => 'rec-1', 'type' => 'A', 'name' => 'alpine.io', 'content' => '144.202.98.245', 'proxied' => true],
                ],
            ]),
        ]);

        $this->service->switchDns($app, dryRun: true);

        // Status should still be dns_switching (not switched) since it's dry run
        // Actually in dry run, the service doesn't update the final status
        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH');
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'purge_cache'));
    }

    public function test_switch_dns_fails_when_no_zone_found(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'nonexistent.io',
            'status' => 'cloned',
            'should_migrate' => true,
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/zones?*' => Http::response([
                'result' => [],
            ]),
        ]);

        $result = $this->service->switchDns($app);

        $this->assertEquals('failed', $result->status);
        $this->assertStringContains('zone not found', $result->last_error);
    }

    // ── DNS Rollback ──────────────────────────────────────────────────

    public function test_rollback_dns_restores_original_ip(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'dns_switched',
            'should_migrate' => true,
            'dns_records_updated' => [
                ['zone_id' => 'zone-abc', 'record_id' => 'rec-1', 'name' => 'alpine.io', 'old_ip' => '144.202.98.245', 'new_ip' => '10.0.0.1'],
            ],
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records/rec-1' => Http::response([
                'result' => ['id' => 'rec-1', 'content' => '144.202.98.245'],
            ]),
            'api.cloudflare.com/client/v4/zones/zone-abc/purge_cache' => Http::response([
                'success' => true,
            ]),
        ]);

        $result = $this->service->rollbackDns($app);

        $this->assertEquals('cloned', $result->status);
        $this->assertNull($result->dns_switched_at);
        $this->assertNull($result->dns_records_updated);

        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && $request->data()['content'] === '144.202.98.245';
        });
    }

    // ── Verification ──────────────────────────────────────────────────

    public function test_verify_marks_successful_site_as_verified(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'dns_switched',
            'should_migrate' => true,
        ]);

        Http::fake([
            'https://alpine.io*' => Http::response('<html><body>Hello Alpine</body></html>'),
        ]);

        $result = $this->service->verify($app);

        $this->assertEquals('verified', $result->status);
        $this->assertTrue($result->verified);
        $this->assertEquals(200, $result->http_status_code);
        $this->assertNotNull($result->verified_at);
    }

    public function test_verify_marks_failed_site_when_500(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'dns_switched',
            'should_migrate' => true,
        ]);

        Http::fake([
            'https://alpine.io*' => Http::response('Internal Server Error', 500),
        ]);

        $result = $this->service->verify($app);

        $this->assertEquals('failed', $result->status);
        $this->assertFalse($result->verified);
        $this->assertEquals(500, $result->http_status_code);
    }

    public function test_verify_marks_failed_when_no_html_closing_tag(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'alpine23',
            'primary_domain' => 'alpine.io',
            'status' => 'dns_switched',
            'should_migrate' => true,
        ]);

        Http::fake([
            'https://alpine.io*' => Http::response('Just some text, no HTML'),
        ]);

        $result = $this->service->verify($app);

        $this->assertEquals('failed', $result->status);
        $this->assertFalse($result->verified);
    }

    // ── Summary ───────────────────────────────────────────────────────

    public function test_get_summary_returns_correct_counts(): void
    {
        ServerMigrationApp::create(['cloudways_app_id' => '1', 'app_label' => 'a', 'status' => 'pending', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '2', 'app_label' => 'b', 'status' => 'pending', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '3', 'app_label' => 'c', 'status' => 'cloned', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '4', 'app_label' => 'd', 'status' => 'failed', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '5', 'app_label' => 'e', 'status' => 'pending', 'should_migrate' => false]);

        $summary = $this->service->getSummary();

        $this->assertEquals(5, $summary['total']);
        $this->assertEquals(4, $summary['migratable']);
        $this->assertEquals(2, $summary['pending']);
        $this->assertEquals(1, $summary['cloned']);
        $this->assertEquals(1, $summary['failed']);
    }

    // ── Model Helpers ─────────────────────────────────────────────────

    public function test_model_can_clone_check(): void
    {
        $pending = new ServerMigrationApp(['status' => 'pending', 'should_migrate' => true]);
        $cloned = new ServerMigrationApp(['status' => 'cloned', 'should_migrate' => true]);
        $notMigrating = new ServerMigrationApp(['status' => 'pending', 'should_migrate' => false]);

        $this->assertTrue($pending->canClone());
        $this->assertFalse($cloned->canClone());
        $this->assertFalse($notMigrating->canClone());
    }

    public function test_model_mark_failed_increments_retry_count(): void
    {
        $app = ServerMigrationApp::create([
            'cloudways_app_id' => '100',
            'app_label' => 'test',
            'status' => 'cloning',
            'should_migrate' => true,
            'retry_count' => 0,
        ]);

        $app->markFailed('Something broke');

        $this->assertEquals('failed', $app->fresh()->status);
        $this->assertEquals(1, $app->fresh()->retry_count);
        $this->assertEquals('Something broke', $app->fresh()->last_error);
    }

    // ── Batch Clone ───────────────────────────────────────────────────

    public function test_clone_batch_skips_already_cloned_apps(): void
    {
        ServerMigrationApp::create(['cloudways_app_id' => '1', 'app_label' => 'a', 'status' => 'cloned', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '2', 'app_label' => 'b', 'status' => 'cloned', 'should_migrate' => true]);

        $stats = $this->service->cloneBatch();

        // No pending apps, so nothing to clone
        $this->assertEquals(0, $stats['cloned']);
    }

    public function test_clone_batch_dry_run_returns_skipped_count(): void
    {
        ServerMigrationApp::create(['cloudways_app_id' => '1', 'app_label' => 'a', 'status' => 'pending', 'should_migrate' => true]);
        ServerMigrationApp::create(['cloudways_app_id' => '2', 'app_label' => 'b', 'status' => 'pending', 'should_migrate' => true]);

        $stats = $this->service->cloneBatch(dryRun: true);

        $this->assertEquals(2, $stats['skipped']);
        $this->assertEquals(0, $stats['cloned']);
    }

    // ── Helpers ────────────────────────────────────────────────────────

    private function fakeCloudwaysAuth(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response([
                'access_token' => 'test-token',
            ]),
        ]);
    }

    private function fakeCloudwaysWithApps(): void
    {
        Http::fake([
            'api.cloudways.com/api/v1/oauth/access_token' => Http::response([
                'access_token' => 'test-token',
            ]),
            'api.cloudways.com/api/v1/server*' => Http::response([
                'servers' => [
                    [
                        'id' => 743387,
                        'label' => 'app76',
                        'apps' => [
                            ['id' => '100', 'label' => 'alpine23', 'cname' => 'alpine-abc.cloudwaysapps.com', 'app_fqdn' => 'alpine.io'],
                            ['id' => '101', 'label' => 'eaca25', 'cname' => 'eaca-xyz.cloudwaysapps.com', 'app_fqdn' => 'eaca.com'],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    /**
     * Custom assertion for string contains (PHPUnit 11 compatible).
     */
    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack);
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
