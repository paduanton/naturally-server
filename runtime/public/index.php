<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

try {
    require __DIR__.'/../vendor/autoload.php';
    $application = require __DIR__.'/../bootstrap/app.php';
    $application->make(Kernel::class)->bootstrap();
} catch (Throwable) {
    error_log('Runtime startup failed.');
    http_response_code(503);
    header('Content-Type: application/problem+json');
    header('Cache-Control: no-store');
    echo '{"type":"about:blank","title":"Service Unavailable","status":503}';
    exit(1);
}

$application->handleRequest(Request::capture());
