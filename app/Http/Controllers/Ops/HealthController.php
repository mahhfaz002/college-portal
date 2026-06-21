<?php
// app/Http/Controllers/Ops/HealthController.php
// Shallow health endpoint for an external uptime monitor. Returns 503 if the
// DB is unreachable so "app up, DB down" is caught.

namespace App\Http\Controllers\Ops;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $startedAt = microtime(true);
        $checks = ['app' => 'ok'];

        try {
            DB::select('select 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'fail';
        }

        $healthy = ! in_array('fail', $checks, true);

        return response()->json([
            'status'     => $healthy ? 'healthy' : 'degraded',
            'checks'     => $checks,
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'ts'         => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
