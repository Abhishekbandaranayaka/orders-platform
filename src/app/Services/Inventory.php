<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class Inventory
{
    private function key(string $sku): string { return "inventory:{$sku}"; }

    public function ensure(string $sku, int $qty): void
    {
        $k = $this->key($sku);
        if (!Redis::exists($k)) {
            Redis::set($k, $qty);
        }
    }

    public function available(string $sku): int
    {
        return (int) (Redis::get($this->key($sku)) ?? 0);
    }

    /**
     * Try to reserve qty. Returns true if reserved, false if insufficient.
     * Uses a naive check-then-decr; good enough for this exercise.
     */
    public function reserve(string $sku, int $qty): bool
    {
        $k = $this->key($sku);
        $avail = (int) (Redis::get($k) ?? 0);
        if ($avail < $qty) return false;
        Redis::decrby($k, $qty);
        return true;
    }

    public function release(string $sku, int $qty): void
    {
        Redis::incrby($this->key($sku), $qty);
    }
}
