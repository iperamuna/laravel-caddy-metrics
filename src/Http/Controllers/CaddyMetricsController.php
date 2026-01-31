<?php

namespace Iperamuna\CaddyMetrics\Http\Controllers;

use Illuminate\Routing\Controller;

class CaddyMetricsController extends Controller
{
    public function index()
    {
        $enabled = config('caddy-metrics.enabled');

        return view('caddy-metrics::index', compact('enabled'));
    }
}
