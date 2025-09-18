<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Log, Storage, DB};
use App\Models\{Customer, Order, OrderItem};
use App\Jobs\ProcessImportedOrder;

class ImportOrders extends Command
{
    protected $signature = 'orders:import {path : CSV absolute or relative to storage/app} {--chunk=1000}';
    protected $description = 'Import orders CSV and enqueue processing jobs';

    public function handle(): int
    {
        $pathArg = $this->argument('path');
        $chunk = (int)$this->option('chunk') ?: 1000;

        // Resolve path (allow relative to storage/app)
        $path = is_file($pathArg) ? $pathArg : storage_path('app/' . ltrim($pathArg, '/'));
        if (!is_file($path)) {
            $this->error("File not found: $path");
            return self::FAILURE;
        }

        $fh = fopen($path, 'r');
        if (!$fh) {
            $this->error("Cannot open: $path");
            return self::FAILURE;
        }

        // Expected headers (you can tweak later)
        // customer_email,customer_name,order_id_external,sku,qty,price_cents,order_total_cents
        $header = fgetcsv($fh);
        if (!$header) {
            $this->error('Empty CSV');
            return self::FAILURE;
        }
        $map = array_flip($header);

        $rejectsPath = 'imports/rejects-'.date('Ymd-His').'.log';
        Storage::makeDirectory('imports');

        $rows = [];
        $line = 1; // after header
        $enqueued = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            $rec = [
                'customer_email' => $row[$map['customer_email']] ?? null,
                'customer_name'  => $row[$map['customer_name']] ?? null,
                'order_ext'      => $row[$map['order_id_external']] ?? null,
                'sku'            => $row[$map['sku']] ?? null,
                'qty'            => (int)($row[$map['qty']] ?? 0),
                'price_cents'    => (int)($row[$map['price_cents']] ?? 0),
                'order_total'    => (int)($row[$map['order_total_cents']] ?? 0),
                'source_file'    => basename($path),
                'line'           => $line,
            ];

            // Minimal validation
            if (!$rec['customer_email'] || !$rec['order_ext'] || !$rec['sku'] || $rec['qty'] <= 0) {
                Storage::append($rejectsPath, "L{$line}: missing required fields: ".json_encode($rec));
                continue;
            }

            $rows[] = $rec;

            if (count($rows) >= $chunk) {
                $enqueued += $this->flushChunk($rows);
                $rows = [];
            }
        }
        if ($rows) {
            $enqueued += $this->flushChunk($rows);
        }

        fclose($fh);

        $this->info("Enqueued $enqueued orders for processing.");
        if (Storage::exists($rejectsPath)) {
            $this->warn("Some rows rejected. See storage/app/$rejectsPath");
        }
        return self::SUCCESS;
    }

    private function flushChunk(array $rows): int
    {
        $count = 0;

        // Group by order_ext so multiple item rows create one order
        $byOrder = [];
        foreach ($rows as $r) {
            $key = $r['order_ext'];
            $byOrder[$key][] = $r;
        }

        foreach ($byOrder as $ext => $items) {
            DB::beginTransaction();
            try {
                // Upsert customer
                $email = $items[0]['customer_email'];
                $name  = $items[0]['customer_name'];
                $customer = Customer::firstOrCreate(
                    ['email' => $email],
                    ['name' => $name]
                );

                // Idempotent order via source_key (file:ext or hash)
                $sourceKey = $items[0]['source_file'] . ':' . $ext;
                $order = Order::firstOrCreate(
                    ['source_key' => $sourceKey],
                    [
                        'customer_id'    => $customer->id,
                        'status'         => 'imported',
                        'payment_status' => 'pending',
                        'total_cents'    => max(0, (int)$items[0]['order_total']),
                        'refunded_cents' => 0,
                        'meta'           => null,
                    ]
                );

                // (Re)create items: simple approach—delete & insert
                OrderItem::where('order_id', $order->id)->delete();
                $sum = 0;
                foreach ($items as $it) {
                    $lineTotal = (int)$it['qty'] * (int)$it['price_cents'];
                    $sum += $lineTotal;
                    OrderItem::create([
                        'order_id'          => $order->id,
                        'sku'               => $it['sku'],
                        'qty'               => (int)$it['qty'],
                        'price_cents'       => (int)$it['price_cents'],
                        'line_total_cents'  => $lineTotal,
                    ]);
                }

                // If CSV didn't provide total, derive it
                if (!$order->wasRecentlyCreated && $order->total_cents == 0) {
                    $order->update(['total_cents' => $sum]);
                } elseif ($order->wasRecentlyCreated && $order->total_cents == 0) {
                    $order->total_cents = $sum;
                    $order->save();
                }

                DB::commit();

                // Enqueue for workflow processing
                ProcessImportedOrder::dispatch($order->id)->onQueue('import');
                $count++;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Import chunk error', ['e' => $e]);
            }
        }
        return $count;
    }
}
