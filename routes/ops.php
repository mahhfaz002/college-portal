<?php
// routes/ops.php
// Register these by adding ONE line to the bottom of routes/api.php:
//     require __DIR__.'/ops.php';
// Living under the "api" group means no CSRF token is required (Sentry/GitHub
// can't send one) and the routes are stateless.
//
// Resulting URLs:
//   GET  /api/health
//   POST /api/webhooks/sentry
//   POST /api/ops/incident-report

use App\Http\Controllers\Ops\HealthController;
use App\Http\Controllers\Ops\ReportTriggerController;
use App\Http\Controllers\Ops\SentryWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::post('/webhooks/sentry', SentryWebhookController::class);
Route::post('/ops/incident-report', ReportTriggerController::class);
