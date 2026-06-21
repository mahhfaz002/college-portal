<?php
// app/Http/Controllers/Ops/ReportTriggerController.php
// The GitHub "incident-report" workflow POSTs here when an auto-fix PR merges.
// Running it on the deployed app means it reuses your existing Mail config,
// so no mail secrets need to be duplicated into CI.

namespace App\Http\Controllers\Ops;

use App\Actions\SendIncidentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportTriggerController
{
    public function __invoke(Request $request, SendIncidentReport $action): JsonResponse
    {
        // Shared-secret auth (constant-time compare).
        $provided = (string) $request->header('X-Ops-Secret', '');
        $expected = (string) config('ops.trigger_secret');
        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $pr = (int) $request->input('pr');
        if ($pr <= 0) {
            return response()->json(['error' => 'missing pr'], 422);
        }

        // Send synchronously here; switch to dispatch() if you wire up queues.
        $action->handle($pr);

        return response()->json(['ok' => true], 202);
    }
}
