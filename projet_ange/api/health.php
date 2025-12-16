<?php
// ================================================================
// api/health.php - Endpoint de santé (monitoring)
// GET → { status, db, uptime, php_version, memory_mb, ts }
// ================================================================
header('Cache-Control: no-cache, no-store');
header('Content-Type: application/json; charset=utf-8');

$start = microtime(true);

$checks = [];

// DB check
try {
    require_once 'db.php';
    $db = getDB();
    $db->query('SELECT 1');
    $checks['db'] = 'ok';
} catch (Throwable $e) {
    $checks['db'] = 'error';
}

// Maintenance file writable
$checks['tmp_writable'] = is_writable('/tmp') ? 'ok' : 'warn';

// Log dir
$checks['log_dir'] = is_dir('/var/log/borne') ? 'ok' : 'warn';

$allOk   = !in_array('error', $checks, true);
$latency = round((microtime(true) - $start) * 1000, 2);

http_response_code($allOk ? 200 : 503);

echo json_encode([
    'status'      => $allOk ? 'ok' : 'degraded',
    'checks'      => $checks,
    'latency_ms'  => $latency,
    'php_version' => PHP_VERSION,
    'memory_mb'   => round(memory_get_usage(true) / 1048576, 2),
    'ts'          => date('c'),
]);
