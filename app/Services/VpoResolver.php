<?php

namespace App\Services;

class VpoResolver
{
    public function __construct(private VpoService $service) {}

    /**
     * Resolve a single VPO account ID to its account data.
     *
     * @return array{id: string, name: string, status: string, ...}|null
     */
    public function resolve(string $accountId): ?array
    {
        return $this->service->getAccount($accountId);
    }

    /**
     * Resolve multiple VPO account IDs to their account data.
     *
     * @param  array<int, string>  $accountIds
     * @return array<string, array|null> Keyed by account ID
     */
    public function resolveMany(array $accountIds): array
    {
        $results = [];

        foreach (array_unique($accountIds) as $id) {
            $results[$id] = $this->resolve($id);
        }

        return $results;
    }
}
