<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Services\Analytics;

class AnalyticsController extends Controller
{
    // GET /api/kpi/today
    public function today()
    {
        $a = new Analytics();
        return response()->json($a->today());
    }

    // GET /api/leaderboard/top?limit=10
    public function leaderboard(Request $req)
    {
        $limit = max(1, min(100, (int)$req->query('limit', 10)));
        // phpredis returns assoc when using bool true
        $assoc = Redis::zrevrange('leaderboard:customers', 0, $limit - 1, true);
        $rows = [];
        foreach ($assoc as $custId => $score) {
            $rows[] = ['customer_id' => (int)$custId, 'spend_cents' => (int)$score];
        }
        return response()->json($rows);
    }
}
