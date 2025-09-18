<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class Analytics
{
    private function daykey(string $suffix): string {
        $day = now()->toDateString(); // YYYY-MM-DD
        return "kpi:{$day}:{$suffix}";
    }

    public function incRevenueCents(int $cents): void {
        if ($cents <= 0) return;
        Redis::incrby($this->daykey('revenue_cents'), $cents);
    }

    public function incOrderCount(int $n = 1): void {
        if ($n <= 0) return;
        Redis::incrby($this->daykey('order_count'), $n);
    }

    public function recomputeAov(): void {
        $rev = (int) (Redis::get($this->daykey('revenue_cents')) ?? 0);
        $cnt = (int) (Redis::get($this->daykey('order_count')) ?? 0);
        $aov = $cnt > 0 ? intdiv($rev, $cnt) : 0;
        Redis::set($this->daykey('aov_cents'), $aov);
    }

    // Customer leaderboard (ZSET)
    public function incrCustomerSpend(int $customerId, int $cents): void {
        if ($cents === 0) return;
        Redis::zincrby('leaderboard:customers', $cents, (string)$customerId);
    }

    // Idempotency token: set-if-not-exists with TTL
    public function once(string $token, int $ttlSeconds = 86400): bool {
        // Returns true if token newly set (i.e., not done before)
        $ok = Redis::setnx("once:{$token}", 1);
        if ($ok) Redis::expire("once:{$token}", $ttlSeconds);
        return $ok;
    }

    // Accessors (optional)
    public function today(): array {
        return [
            'revenue_cents' => (int) (Redis::get($this->daykey('revenue_cents')) ?? 0),
            'order_count'   => (int) (Redis::get($this->daykey('order_count')) ?? 0),
            'aov_cents'     => (int) (Redis::get($this->daykey('aov_cents')) ?? 0),
        ];
    }
}
