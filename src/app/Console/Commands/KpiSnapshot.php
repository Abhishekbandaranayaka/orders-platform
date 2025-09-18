<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Analytics;

class KpiSnapshot extends Command
{
    protected $signature = 'kpi:snapshot {--date=}';
    protected $description = 'Persist today\'s (or given) Redis KPIs into kpi_daily table';

    public function handle(): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $analytics = new Analytics();
        $today = $analytics->today();

        // Compute AOV if missing
        $aov = $today['order_count'] > 0 ? intdiv($today['revenue_cents'], $today['order_count']) : 0;

        DB::table('kpi_daily')->updateOrInsert(
            ['date' => $date],
            [
                'revenue_cents' => $today['revenue_cents'],
                'order_count'   => $today['order_count'],
                'aov_cents'     => $aov,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        $this->info("Snapshot saved for {$date}: revenue={$today['revenue_cents']}c, orders={$today['order_count']}, aov={$aov}c");
        return self::SUCCESS;
    }
}
