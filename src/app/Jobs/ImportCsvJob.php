<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $path, public int $chunk = 1000) {}

    public function viaQueue(): string { return 'import'; }

    public function handle(): void
    {
        // Reuse the CLI command from Step 3
        \Artisan::call('orders:import', ['path' => $this->path, '--chunk' => $this->chunk]);
    }
}
