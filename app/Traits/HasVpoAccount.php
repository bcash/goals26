<?php

namespace App\Traits;

use App\Services\VpoResolver;

trait HasVpoAccount
{
    /**
     * Resolve the linked VPO account data.
     *
     * Uses once() to prevent N+1 when accessed multiple times per request.
     *
     * @return array{id: string, name: string, status: string, ...}|null
     */
    public function vpoAccount(): ?array
    {
        if (! $this->vpo_account_id) {
            return null;
        }

        return once(fn () => app(VpoResolver::class)->resolve($this->vpo_account_id));
    }

    /**
     * Get the VPO account name, or null if not linked/unavailable.
     */
    public function vpoAccountName(): ?string
    {
        return $this->vpoAccount()['name'] ?? null;
    }
}
