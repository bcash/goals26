<?php

namespace Tests\Feature;

use App\Services\CloudflareApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareApiClientTest extends TestCase
{
    use RefreshDatabase;

    private CloudflareApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new CloudflareApiClient;
        config([
            'services.cloudflare.base_url' => 'https://api.cloudflare.com/client/v4',
            'services.cloudflare.api_token' => 'test-cf-token',
        ]);
    }

    public function test_get_zone_by_domain_returns_zone(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'result' => [
                    ['id' => 'zone-abc', 'name' => 'alpine.io', 'status' => 'active'],
                ],
                'success' => true,
            ]),
        ]);

        $zone = $this->client->getZoneByDomain('alpine.io');

        $this->assertNotNull($zone);
        $this->assertEquals('zone-abc', $zone['id']);
    }

    public function test_get_zone_by_subdomain_extracts_root_domain(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'result' => [
                    ['id' => 'zone-xyz', 'name' => 'warmsprings-nsn.gov'],
                ],
                'success' => true,
            ]),
        ]);

        $zone = $this->client->getZoneByDomain('visit.warmsprings-nsn.gov');

        $this->assertNotNull($zone);
        $this->assertEquals('zone-xyz', $zone['id']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'name=warmsprings-nsn.gov');
        });
    }

    public function test_get_zone_returns_null_when_not_found(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'result' => [],
                'success' => true,
            ]),
        ]);

        $zone = $this->client->getZoneByDomain('nonexistent.com');

        $this->assertNull($zone);
    }

    public function test_list_dns_records_returns_records(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records*' => Http::response([
                'result' => [
                    ['id' => 'rec-1', 'type' => 'A', 'name' => 'alpine.io', 'content' => '144.202.98.245', 'proxied' => true],
                    ['id' => 'rec-2', 'type' => 'A', 'name' => 'www.alpine.io', 'content' => '144.202.98.245', 'proxied' => true],
                ],
                'success' => true,
            ]),
        ]);

        $records = $this->client->listDnsRecords('zone-abc', 'A');

        $this->assertCount(2, $records);
        $this->assertEquals('144.202.98.245', $records[0]['content']);
    }

    public function test_find_a_records_by_ip_filters_correctly(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records*' => Http::response([
                'result' => [
                    ['id' => 'rec-1', 'type' => 'A', 'name' => 'alpine.io', 'content' => '144.202.98.245'],
                    ['id' => 'rec-2', 'type' => 'A', 'name' => 'other.io', 'content' => '10.0.0.1'],
                    ['id' => 'rec-3', 'type' => 'A', 'name' => 'www.alpine.io', 'content' => '144.202.98.245'],
                ],
                'success' => true,
            ]),
        ]);

        $records = $this->client->findARecordsByIp('zone-abc', '144.202.98.245');

        $this->assertCount(2, $records);
        $this->assertEquals('alpine.io', $records[0]['name']);
        $this->assertEquals('www.alpine.io', $records[1]['name']);
    }

    public function test_update_dns_record_sends_patch(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records/rec-1' => Http::response([
                'result' => ['id' => 'rec-1', 'content' => '10.0.0.1'],
                'success' => true,
            ]),
        ]);

        $result = $this->client->updateDnsRecord('zone-abc', 'rec-1', [
            'content' => '10.0.0.1',
        ]);

        $this->assertEquals('10.0.0.1', $result['content']);

        Http::assertSent(function ($request) {
            return $request->method() === 'PATCH'
                && $request->data()['content'] === '10.0.0.1';
        });
    }

    public function test_purge_cache_returns_true_on_success(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/purge_cache' => Http::response([
                'success' => true,
            ]),
        ]);

        $result = $this->client->purgeCache('zone-abc');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->data()['purge_everything'] === true;
        });
    }

    public function test_purge_cache_returns_false_on_failure(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/purge_cache' => Http::response('Error', 500),
        ]);

        $result = $this->client->purgeCache('zone-abc');

        $this->assertFalse($result);
    }

    public function test_connection_test_returns_true_on_success(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response([
                'result' => [['id' => 'zone-abc']],
                'success' => true,
            ]),
        ]);

        $this->assertTrue($this->client->testConnection());
    }

    public function test_connection_test_returns_false_on_failure(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones*' => Http::response('Unauthorized', 401),
        ]);

        $this->assertFalse($this->client->testConnection());
    }

    public function test_list_dns_records_returns_empty_on_failure(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/zone-abc/dns_records*' => Http::response('Error', 500),
        ]);

        $result = $this->client->listDnsRecords('zone-abc');

        $this->assertEquals([], $result);
    }
}
