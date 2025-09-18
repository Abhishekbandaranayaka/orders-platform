<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Inventory;

class InventorySeed extends Command
{
    protected $signature = 'inventory:seed {--qty=100000} {--skus=SKU-1,SKU-2,SKU-3,SKU-4}';
    protected $description = 'Seed Redis inventory keys for given SKUs';

    public function handle(): int
    {
        $qty  = (int)$this->option('qty');
        $skus = array_filter(array_map('trim', explode(',', (string)$this->option('skus'))));
        $inv  = new Inventory();

        foreach ($skus as $sku) {
            $inv->ensure($sku, $qty);
            $this->line("Ensured {$sku} => {$qty}");
        }
        $this->info('Inventory seeded.');
        return self::SUCCESS;
    }
}
