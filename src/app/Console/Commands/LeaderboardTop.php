<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class LeaderboardTop extends Command
{
    protected $signature = 'leaderboard:top {--limit=10}';
    protected $description = 'Show top N customers by spend from Redis';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        // Try phpredis associative form first
        $rows = Redis::zrevrange('leaderboard:customers', 0, $limit - 1, true);

        $this->info("Top {$limit} customers");

        if (is_array($rows) && !empty($rows)) {
            $rank = 1;
            foreach ($rows as $member => $score) {
                $this->line(sprintf('  #%d customer_id=%s spend_cents=%d', $rank++, (string)$member, (int)$score));
            }
            return self::SUCCESS;
        }

        // Fallback: Predis can return a flat array if WITHSCORES is used as a string
        $flat = Redis::zrevrange('leaderboard:customers', 0, $limit - 1, 'WITHSCORES');
        if (is_array($flat) && !empty($flat)) {
            for ($i = 0; $i < count($flat); $i += 2) {
                $member = $flat[$i] ?? '(?)';
                $score  = (int)($flat[$i + 1] ?? 0);
                $this->line(sprintf('  #%d customer_id=%s spend_cents=%d', ($i/2) + 1, (string)$member, $score));
            }
            return self::SUCCESS;
        }

        $this->line('  (empty)');
        return self::SUCCESS;
    }
}
