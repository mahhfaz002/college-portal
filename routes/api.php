<?php

use Illuminate\Support\Facades\Route;

// Self-healing ops pipeline routes (health check, Sentry webhook, incident report).
// API routing is registered in bootstrap/app.php via withRouting(api: ...).
require __DIR__.'/ops.php';
