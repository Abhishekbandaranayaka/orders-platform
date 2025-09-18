<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Pass the API token to the Blade so the page can call /api endpoints.
        $apiToken = config('app.api_token', env('API_TOKEN', ''));
        return view('dashboard', ['apiToken' => $apiToken]);
    }
}
