<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Jobs\ImportCsvJob;
use App\Jobs\ProcessRefund;

class OrdersController extends Controller
{
    // POST /api/orders/import
    public function import(Request $req)
    {
        $validated = $req->validate([
            'path'  => 'sometimes|string', // storage/app relative or absolute
            'chunk' => 'sometimes|integer|min:100|max:5000',
            'file'  => 'sometimes|file|mimes:csv,txt',
        ]);

        // If uploading a file, stash it into storage/app/imports/ and use that path
        if ($req->hasFile('file')) {
            $stored = $req->file('file')->storeAs('imports', 'api-'.Str::uuid().'.csv');
            $path = storage_path('app/'.$stored);
        } else {
            $path = isset($validated['path']) ? (is_file($validated['path']) ? $validated['path'] : storage_path('app/'.ltrim($validated['path'], '/'))) : null;
        }

        if (!$path || !is_file($path)) {
            return response()->json(['error' => 'CSV not found'], 422);
        }

        $chunk = $validated['chunk'] ?? 1000;
        ImportCsvJob::dispatch($path, $chunk)->onQueue('import');

        return response()->json(['queued' => true, 'path' => $path, 'chunk' => $chunk]);
    }

    // POST /api/orders/{id}/refund
    public function refund(Request $req, int $id)
    {
        $validated = $req->validate([
            'amount_cents' => 'required|integer|min:1',
            'key'          => 'nullable|string',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) return response()->json(['error' => 'Order not found'], 404);

        // Was: $key = $validated['key'] ?: ('ord'.$id.'-amt'.$validated['amount_cents']);
        // Use null coalescing so it doesn't error when 'key' isn't sent:
        $key = $validated['key'] ?? ('api-'.$id.'-amt'.$validated['amount_cents'].'-'.\Illuminate\Support\Str::uuid());

        // Idempotent insert (if the SAME key is re-used later)
        $exists = DB::table('refunds')->where('idempotency_key',$key)->first();
        if ($exists) {
            return response()->json(['queued' => true, 'refund_id' => $exists->id, 'idempotent' => true, 'key' => $key]);
        }

        $refundId = DB::table('refunds')->insertGetId([
            'order_id'        => $id,
            'amount_cents'    => (int)$validated['amount_cents'],
            'status'          => 'queued',
            'idempotency_key' => $key,
            'meta'            => json_encode(['via'=>'api','requested_at'=>now()->toISOString()]),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        \App\Jobs\ProcessRefund::dispatch($refundId)->onQueue('refunds');

        return response()->json(['queued' => true, 'refund_id' => $refundId, 'key' => $key]);
    }

}
