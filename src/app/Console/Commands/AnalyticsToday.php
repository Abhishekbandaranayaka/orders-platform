<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Analytics;

class AnalyticsToday extends Command
{
    protected $signature = 'analytics:today';
    protected $description = 'Print Redis KPIs for today';

    public function handle(): int
    {
        $a = new Analytics();
        $t = $a->today();
        $this->info('Today KPIs');
        $this->line('  revenue_cents: '.$t['revenue_cents']);
        $this->line('  order_count:   '.$t['order_count']);
        $this->line('  aov_cents:     '.$t['aov_cents']);
        return self::SUCCESS;
    }
}
